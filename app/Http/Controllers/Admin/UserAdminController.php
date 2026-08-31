<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustUserCreditsRequest;
use App\Http\Requests\Admin\AdminReasonRequest;
use App\Http\Requests\Admin\BulkUsersRequest;
use App\Http\Requests\Admin\DestroyUserRequest;
use App\Http\Requests\Admin\UpdateAdminUserPlanRequest;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Models\ActivityLog;
use App\Models\AdminAuditLog;
use App\Models\Announcement;
use App\Models\Review;
use App\Models\TokenPurchase;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Models\WorkLog;
use App\Support\Admin\AdminAudit;
use App\Support\Admin\AdminResponse;
use App\Support\Admin\AnnouncementService;
use App\Support\Admin\ApprovalService;
use App\Models\StaffCaseReferral;
use App\Support\Admin\StaffCaseReferralService;
use App\Support\NigeriaLocations;
use App\Support\NumberFormat;
use App\Support\Tokens\TokenWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserAdminController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', $this->indexProps());
    }

    public function show(Request $request, User $user): Response|JsonResponse|RedirectResponse
    {
        abort_unless($user->isRegularUser(), 404);

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json($this->panel($user, $request->user()));
        }

        return Inertia::render('Admin/Users/Index', [
            ...$this->indexProps(),
            'opened_id' => $user->id,
        ]);
    }

    public function suspend(AdminReasonRequest $request, User $user, ApprovalService $approvals): JsonResponse|RedirectResponse
    {
        $reason = $request->validated('reason');
        $result = $this->suspendThroughApproval($request->user(), $user, $reason, $approvals);

        if (($result['status'] ?? '') === 'pending') {
            return AdminResponse::mutation($request, [
                'type' => 'info',
                'title' => 'Approval requested',
                'message' => 'A Super Admin must approve this suspension before it takes effect.',
            ]);
        }

        $user = $result['user'];

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'User suspended',
            'message' => $user->displayBusinessName().' can no longer sign in.',
        ], ['user' => $this->listPayload($user->loadCount(['workLogs', 'reviews']))]);
    }

    public function reinstate(AdminReasonRequest $request, User $user, ApprovalService $approvals): JsonResponse|RedirectResponse
    {
        abort_unless($user->isRegularUser(), 403);

        $reason = $request->validated('reason');
        $old = ['suspended_at' => $user->suspended_at?->toIso8601String(), 'reason' => $user->suspension_reason];

        $result = $approvals->run(
            $request->user(),
            'users.reinstate',
            $user,
            $reason,
            [
                'user_id' => $user->id,
                'reason' => $reason,
                'old' => $old,
                'new' => ['suspended_at' => null, 'reason' => $reason],
            ],
            function () use ($user, $reason, $old, $request) {
                $user->forceFill([
                    'suspended_at' => null,
                    'suspension_reason' => null,
                ])->save();

                AdminAudit::record(
                    'users.reinstated',
                    "{$request->user()->name} reinstated {$user->email}: {$reason}",
                    $user,
                    $old,
                    ['suspended_at' => null, 'reason' => $reason],
                );
            },
        );

        if (($result['status'] ?? '') === 'pending') {
            return AdminResponse::mutation($request, [
                'type' => 'info',
                'title' => 'Approval requested',
                'message' => 'A Super Admin must approve this reinstatement before it takes effect.',
            ]);
        }

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'User reinstated',
            'message' => $user->displayBusinessName().' can sign in again.',
        ], ['user' => $this->listPayload($user->fresh()->loadCount(['workLogs', 'reviews']))]);
    }

    public function verify(AdminReasonRequest $request, User $user): JsonResponse|RedirectResponse
    {
        abort_unless($user->isRegularUser(), 403);

        $reason = $request->validated('reason');
        $old = ['email_verified_at' => $user->email_verified_at?->toIso8601String()];

        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();

        AdminAudit::record(
            'users.verified',
            "{$request->user()->name} verified {$user->email}: {$reason}",
            $user,
            $old,
            ['email_verified_at' => $user->email_verified_at?->toIso8601String(), 'reason' => $reason],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Email verified',
            'message' => $user->email.' is marked as verified.',
        ], ['user' => $this->listPayload($user->fresh()->loadCount(['workLogs', 'reviews']))]);
    }

    public function unverify(AdminReasonRequest $request, User $user): JsonResponse|RedirectResponse
    {
        abort_unless($user->isRegularUser(), 403);

        $reason = $request->validated('reason');
        $old = ['email_verified_at' => $user->email_verified_at?->toIso8601String()];

        $user->forceFill(['email_verified_at' => null])->save();

        AdminAudit::record(
            'users.unverified',
            "{$request->user()->name} unmarked {$user->email} as unverified: {$reason}",
            $user,
            $old,
            ['email_verified_at' => null, 'reason' => $reason],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Verification cleared',
            'message' => $user->email.' is unverified again.',
        ], ['user' => $this->listPayload($user->fresh()->loadCount(['workLogs', 'reviews']))]);
    }

    public function update(UpdateAdminUserRequest $request, User $user): JsonResponse|RedirectResponse
    {
        abort_unless($user->isRegularUser(), 403);

        $data = $request->validated();
        $reason = $data['reason'];
        unset($data['reason']);

        $old = $user->only(['first_name', 'last_name', 'business_name', 'trade', 'bio', 'whatsapp', 'state', 'lga', 'slug']);

        if (! empty($data['slug']) && $data['slug'] !== $user->slug) {
            $taken = User::query()->where('slug', $data['slug'])->where('id', '!=', $user->id)->exists();
            abort_if($taken, 422, 'That public URL is already taken.');
        }

        $user->fill($data)->save();

        AdminAudit::record(
            'users.profile_updated',
            "{$request->user()->name} edited {$user->email}: {$reason}",
            $user,
            $old,
            [...$user->only(array_keys($old)), 'reason' => $reason],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Profile updated',
            'message' => 'The artisan’s details were saved.',
        ], ['panel' => $this->panel($user->fresh())]);
    }

    public function updatePlan(UpdateAdminUserPlanRequest $request, User $user): JsonResponse|RedirectResponse
    {
        abort_unless($user->isRegularUser(), 403);

        $data = $request->validated();
        $old = ['plan' => $user->plan, 'annual_expires_at' => $user->annual_expires_at?->toDateString()];

        $plan = $data['plan'];
        $expires = $plan === 'annual' ? $data['annual_expires_at'] : null;

        $user->forceFill([
            'plan' => $plan === 'annual' ? 'annual' : ($plan === 'payg' ? 'payg' : 'free'),
            'annual_expires_at' => $expires,
        ])->save();

        AdminAudit::record(
            'users.plan_changed',
            "{$request->user()->name} set {$user->email} to {$plan}: {$data['reason']}",
            $user,
            $old,
            ['plan' => $user->plan, 'annual_expires_at' => $user->annual_expires_at?->toDateString(), 'reason' => $data['reason']],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Plan updated',
            'message' => $user->displayBusinessName().' is now on '.$this->planLabel($user->fresh()).'.',
        ], ['panel' => $this->panel($user->fresh())]);
    }

    public function adjustCredits(AdjustUserCreditsRequest $request, User $user, TokenWallet $wallet): JsonResponse|RedirectResponse
    {
        abort_unless($user->isRegularUser(), 403);

        $data = $request->validated();
        $before = (int) $user->token_balance;

        try {
            if ($data['direction'] === 'credit') {
                $wallet->credit($user, (int) $data['amount'], TokenTransaction::ACTION_ADMIN_ADJUST, $data['reason']);
            } else {
                $wallet->debit($user, (int) $data['amount'], TokenTransaction::ACTION_REFUND, $data['reason']);
            }
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'amount' => $exception->getMessage(),
            ]);
        }

        $fresh = $user->fresh();

        AdminAudit::record(
            'credits.adjusted',
            "{$request->user()->name} {$data['direction']}ed {$data['amount']} tokens for {$user->email}: {$data['reason']}",
            $user,
            ['token_balance' => $before],
            ['token_balance' => (int) $fresh->token_balance, 'reason' => $data['reason']],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Balance updated',
            'message' => "{$user->email} is now at {$fresh->token_balance} tokens.",
        ], ['panel' => $this->panel($fresh)]);
    }

    public function impersonate(AdminReasonRequest $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->canDo('admin.users.impersonate'), 403);
        abort_unless($user->isRegularUser(), 403);
        abort_if($user->is($request->user()), 403);

        $admin = $request->user();
        $reason = $request->validated('reason');

        $request->session()->put('impersonator_id', $admin->id);

        Auth::login($user);
        $request->session()->regenerate();

        AdminAudit::record(
            'users.impersonated',
            "{$admin->name} viewed Isabi as {$user->email}: {$reason}",
            $user,
            null,
            ['impersonator_id' => $admin->id, 'reason' => $reason],
            $admin,
        );

        return redirect()
            ->route('dashboard')
            ->with('toast', [
                'type' => 'success',
                'title' => 'Viewing as artisan',
                'message' => 'You are seeing '.$user->displayBusinessName().'’s account.',
            ]);
    }

    public function leaveImpersonation(Request $request): RedirectResponse
    {
        $adminId = $request->session()->pull('impersonator_id');
        abort_unless($adminId, 403);

        $viewedId = $request->user()?->id;
        $admin = User::query()->findOrFail($adminId);
        abort_unless($admin->isStaff(), 403);

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()
            ->route('admin.users.show', $viewedId ?: $admin->id)
            ->with('toast', [
                'type' => 'success',
                'title' => 'Back to admin',
                'message' => 'You left the artisan view.',
            ]);
    }

    public function forceLogout(AdminReasonRequest $request, User $user): JsonResponse|RedirectResponse
    {
        abort_unless($user->isRegularUser(), 403);

        $reason = $request->validated('reason');
        $this->forgetSessions($user);
        $user->setRememberToken(Str::random(60));
        $user->save();

        AdminAudit::record(
            'users.force_logout',
            "{$request->user()->name} forced {$user->email} to sign out: {$reason}",
            $user,
            null,
            ['reason' => $reason],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Sessions cleared',
            'message' => 'They’ll need to sign in again on every device.',
        ]);
    }

    public function sendPasswordReset(AdminReasonRequest $request, User $user): JsonResponse|RedirectResponse
    {
        abort_unless($user->isRegularUser(), 403);

        $reason = $request->validated('reason');
        Password::broker()->sendResetLink(['email' => $user->email]);

        AdminAudit::record(
            'users.password_reset',
            "{$request->user()->name} sent a password reset to {$user->email}: {$reason}",
            $user,
            null,
            ['reason' => $reason],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Reset email sent',
            'message' => 'A password reset link is on its way to '.$user->email.'.',
        ]);
    }

    public function destroy(DestroyUserRequest $request, User $user, ApprovalService $approvals): JsonResponse|RedirectResponse
    {
        abort_unless($user->isRegularUser(), 403);

        $reason = $request->validated('reason');
        $confirmation = $request->validated('confirmation');
        $email = $user->email;
        $name = $user->displayBusinessName();

        $result = $approvals->run(
            $request->user(),
            'users.delete',
            $user,
            $reason,
            [
                'user_id' => $user->id,
                'reason' => $reason,
                'confirmation' => $confirmation,
                'old' => ['email' => $email],
                'new' => ['deleted_at' => now()->toIso8601String(), 'reason' => $reason],
            ],
            function () use ($user, $reason, $request, $email) {
                $this->forgetSessions($user);
                $user->delete();

                AdminAudit::record(
                    'users.deleted',
                    "{$request->user()->name} soft-deleted {$email}: {$reason}",
                    $user,
                    ['email' => $email],
                    ['reason' => $reason, 'deleted_at' => now()->toIso8601String()],
                );

                return ['deleted_id' => $user->id];
            },
        );

        if (($result['status'] ?? '') === 'pending') {
            return AdminResponse::mutation($request, [
                'type' => 'info',
                'title' => 'Approval requested',
                'message' => 'A Super Admin must approve deleting this account before it takes effect.',
            ]);
        }

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Account deleted',
            'message' => $name.' is hidden from the platform and can still be recovered from the database.',
        ], ['deleted_id' => $user->id]);
    }

    public function bulkSuspend(BulkUsersRequest $request, ApprovalService $approvals): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $reason = $data['reason'];
        $executedIds = [];
        $pendingCount = 0;

        User::query()
            ->artisans()
            ->whereIn('id', $data['ids'])
            ->whereNull('suspended_at')
            ->get()
            ->each(function (User $user) use ($request, $reason, $approvals, &$executedIds, &$pendingCount) {
                if (! $user->isRegularUser()) {
                    return;
                }

                $result = $this->suspendThroughApproval($request->user(), $user, $reason, $approvals);

                if (($result['status'] ?? '') === 'pending') {
                    $pendingCount++;

                    return;
                }

                $executedIds[] = $user->id;
            });

        $executedCount = count($executedIds);

        if ($pendingCount > 0 && $executedCount === 0) {
            return AdminResponse::mutation($request, [
                'type' => 'info',
                'title' => 'Approval requested',
                'message' => $pendingCount === 1
                    ? 'A Super Admin must approve this suspension before it takes effect.'
                    : "{$pendingCount} suspensions sent for Super Admin approval.",
            ], [
                'pending_count' => $pendingCount,
                'executed_ids' => $executedIds,
            ]);
        }

        if ($pendingCount > 0) {
            return AdminResponse::mutation($request, [
                'type' => 'info',
                'title' => 'Partly submitted',
                'message' => "{$executedCount} suspended now. {$pendingCount} waiting on Super Admin approval.",
            ], [
                'pending_count' => $pendingCount,
                'executed_ids' => $executedIds,
            ]);
        }

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Suspended',
            'message' => $executedCount.' artisan'.($executedCount === 1 ? '' : 's').' can no longer sign in.',
        ], [
            'count' => $executedCount,
            'executed_ids' => $executedIds,
        ]);
    }

    public function bulkMessage(BulkUsersRequest $request, AnnouncementService $announcements): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        $announcement = Announcement::query()->create([
            'audience' => Announcement::AUDIENCE_USERS,
            'title' => $data['subject'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'channels' => $announcements->channelsFor(Announcement::AUDIENCE_USERS, $data['channels']),
            'segment' => ['user_ids' => $data['ids']],
            'status' => Announcement::STATUS_DRAFT,
            'created_by_user_id' => $request->user()->id,
        ]);

        $announcements->queue($announcement);

        AdminAudit::record(
            'announcement.sent',
            "{$request->user()->name} messaged ".count($data['ids']).' artisans: '.$data['reason'],
            $announcement,
            null,
            ['reason' => $data['reason'], 'user_ids' => $data['ids']],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Sending',
            'message' => 'Messages are being queued for '.count($data['ids']).' artisans.',
        ]);
    }

    public function bulkExport(BulkUsersRequest $request): StreamedResponse
    {
        $ids = $request->validated('ids');
        $filename = 'isabi-users-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($ids) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Business', 'Email', 'Trade', 'State', 'LGA', 'Plan', 'Jobs', 'Reviews', 'Credits', 'Verified', 'Suspended', 'Joined']);

            User::query()
                ->artisans()
                ->withCount(['workLogs', 'reviews'])
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->get()
                ->each(function (User $user) use ($handle) {
                    fputcsv($handle, [
                        $user->name,
                        $user->displayBusinessName(),
                        $user->email,
                        $user->trade,
                        $user->state,
                        $user->lga,
                        $this->planLabel($user),
                        $user->work_logs_count,
                        $user->reviews_count,
                        $user->token_balance,
                        $user->email_verified_at ? 'yes' : 'no',
                        $user->suspended_at ? 'yes' : 'no',
                        $user->created_at?->toDateString(),
                    ]);
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function indexProps(): array
    {
        $users = User::query()
            ->artisans()
            ->withCount(['workLogs', 'reviews'])
            ->withSum([
                'tokenPurchases as revenue' => fn ($query) => $query->where('status', TokenPurchase::STATUS_COMPLETED),
            ], 'price')
            ->withMax('workLogs', 'created_at')
            ->latest('id')
            ->limit(2500)
            ->get();

        $lastLogins = ActivityLog::query()
            ->selectRaw('user_id, MAX(created_at) as last_at')
            ->whereIn('user_id', $users->pluck('id'))
            ->where('action', 'auth.login')
            ->groupBy('user_id')
            ->pluck('last_at', 'user_id');

        return [
            'users' => $users->map(fn (User $user) => $this->listPayload($user, $lastLogins->get($user->id)))->values(),
            'trades' => config('trades'),
            'states' => NigeriaLocations::states(),
            'opened_id' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function panel(User $user, ?User $viewer = null): array
    {
        $viewer ??= request()->user();
        $canSeeFinancials = $viewer?->isSuperAdmin() || $viewer?->canDo('admin.credits.view');

        $user->loadCount(['workLogs', 'reviews', 'tokenPurchases', 'referralsMade']);

        $jobs = WorkLog::query()
            ->where('user_id', $user->id)
            ->with('review:id,work_log_id,rating')
            ->latest('id')
            ->limit(400)
            ->get()
            ->map(fn (WorkLog $log) => $this->jobPayload($log));

        $reviews = Review::query()
            ->where('user_id', $user->id)
            ->with('workLog:id,uid,description,client_name,worked_on,review_requested_at')
            ->latest('id')
            ->limit(400)
            ->get()
            ->map(fn (Review $review) => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'client' => $review->client_display_name,
                'flagged' => $review->flagged_at !== null,
                'hidden' => $review->hidden_at !== null,
                'removed' => $review->removed_at !== null,
                'submitted_at' => ($review->submitted_at ?? $review->created_at)?->timezone(config('app.display_timezone'))->format('j M Y · g:ia'),
                'job' => $review->workLog?->description,
            ]);

        $requests = WorkLog::query()
            ->where('user_id', $user->id)
            ->whereNotNull('review_requested_at')
            ->with('review:id,work_log_id')
            ->latest('review_requested_at')
            ->limit(400)
            ->get()
            ->map(function (WorkLog $log) {
                $status = 'sent';
                if ($log->review) {
                    $status = 'completed';
                } elseif ($log->review_token_expires_at?->isPast()) {
                    $status = 'no-response';
                }

                return [
                    'id' => $log->id,
                    'uid' => $log->uid,
                    'description' => $log->description,
                    'client' => $log->client_name,
                    'requested_at' => $log->review_requested_at?->timezone(config('app.display_timezone'))->format('j M Y · g:ia'),
                    'status' => $status,
                ];
            });

        $logins = ActivityLog::query()
            ->where('user_id', $user->id)
            ->whereIn('action', ['auth.login', 'auth.logout'])
            ->latest('created_at')
            ->limit(80)
            ->get()
            ->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'title' => $log->titleFromAction(),
                'summary' => $log->summary,
                'ip' => $log->ip_address,
                'when' => $log->created_at?->timezone(config('app.display_timezone'))->format('j M Y · g:ia'),
            ]);

        $adminActions = AdminAuditLog::query()
            ->with('actor:id,name,email')
            ->where(function ($query) use ($user) {
                $query->where(function ($inner) use ($user) {
                    $inner->where('subject_type', $user->getMorphClass())
                        ->where('subject_id', $user->id);
                })->orWhere('summary', 'like', '%'.$user->email.'%');
            })
            ->latest('id')
            ->limit(80)
            ->get()
            ->map(fn (AdminAuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'summary' => $log->summary,
                'actor' => $log->actor?->name,
                'when' => $log->created_at?->timezone(config('app.display_timezone'))->format('j M Y · g:ia'),
            ]);

        $detail = $this->detailPayload($user);
        $credits = [
            'balance' => null,
            'transactions' => [],
            'purchases' => [],
        ];

        if ($canSeeFinancials) {
            $transactions = TokenTransaction::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->limit(400)
                ->get()
                ->map(fn (TokenTransaction $tx) => [
                    'id' => $tx->id,
                    'type' => $tx->type,
                    'amount' => $tx->amount,
                    'action' => $tx->action,
                    'description' => $tx->description,
                    'when' => $tx->created_at?->timezone(config('app.display_timezone'))->format('j M Y · g:ia'),
                ]);

            $purchases = TokenPurchase::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->limit(200)
                ->get()
                ->map(fn (TokenPurchase $purchase) => [
                    'id' => $purchase->id,
                    'pack_name' => $purchase->pack_name,
                    'tokens' => $purchase->tokens,
                    'price' => $purchase->price,
                    'status' => $purchase->status,
                    'when' => ($purchase->paid_at ?? $purchase->created_at)?->timezone(config('app.display_timezone'))->format('j M Y · g:ia'),
                ]);

            $revenue = (int) TokenPurchase::query()
                ->where('user_id', $user->id)
                ->where('status', TokenPurchase::STATUS_COMPLETED)
                ->sum('price');

            $detail['revenue'] = $revenue;
            $detail['revenue_label'] = $this->revenueLabel($revenue);
            $credits = [
                'balance' => (int) $user->token_balance,
                'transactions' => $transactions,
                'purchases' => $purchases,
            ];
        } else {
            unset($detail['token_balance'], $detail['revenue'], $detail['revenue_label']);
            $detail['financials_hidden'] = true;
        }

        return [
            'user' => $detail,
            'jobs' => $jobs,
            'reviews' => $reviews,
            'review_requests' => $requests,
            'credits' => $credits,
            'activity' => [
                'logins' => $logins,
                'admin_actions' => $adminActions,
            ],
            'can_see_financials' => (bool) $canSeeFinancials,
            'escalation' => app(StaffCaseReferralService::class)->escalationBlockFor(
                StaffCaseReferral::SUBJECT_USER,
                $user->id,
            ),
            'can_escalate' => $viewer
                && app(StaffCaseReferralService::class)->canEscalate($viewer, StaffCaseReferral::SUBJECT_USER),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function listPayload(User $user, mixed $lastLogin = null): array
    {
        $jobAt = $user->work_logs_max_created_at ?? null;
        $active = collect([$jobAt, $lastLogin, $user->created_at])->filter()->map(fn ($value) => $value instanceof \DateTimeInterface ? $value : Carbon::parse($value))->sort()->last();

        return [
            'id' => $user->id,
            'uid' => $user->uid,
            'uid_kind' => \App\Support\Identity\UserUid::isStaffUid($user->uid) ? 'staff' : 'user',
            'name' => $user->name,
            'business_name' => $user->displayBusinessName(),
            'email' => $user->email,
            'trade' => $user->trade,
            'state' => $user->state,
            'lga' => $user->lga,
            'plan' => $this->planLabel($user),
            'plan_key' => $user->plan,
            'token_balance' => (int) $user->token_balance,
            'jobs' => (int) ($user->work_logs_count ?? $user->workLogs()->count()),
            'reviews' => (int) ($user->reviews_count ?? $user->reviews()->count()),
            'revenue' => (int) ($user->revenue ?? 0),
            'revenue_label' => $this->revenueLabel((int) ($user->revenue ?? 0)),
            'suspended' => $user->suspended_at !== null,
            'verified' => $user->email_verified_at !== null,
            'avatar_url' => $user->avatar_url,
            'public_url' => $user->publicUrl(),
            'joined' => $user->created_at?->timezone(config('app.display_timezone'))->format('j M Y'),
            'joined_short' => $user->created_at?->timezone(config('app.display_timezone'))->format('M Y'),
            'created_iso' => $user->created_at?->toIso8601String(),
            'last_active_iso' => $active?->toIso8601String(),
            'profile_completion' => (int) $user->profile_completion,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailPayload(User $user): array
    {
        return [
            ...$this->listPayload($user),
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'slug' => $user->slug,
            'whatsapp' => $user->whatsapp,
            'bio' => $user->bio,
            'office_address' => $user->office_address,
            'plan_key' => $user->plan ?: 'free',
            'annual_expires_at' => $user->annual_expires_at?->toDateString(),
            'suspension_reason' => $user->suspension_reason,
            'email_verified_at' => $user->email_verified_at?->timezone(config('app.display_timezone'))->format('j M Y · g:ia'),
            'purchases_count' => (int) ($user->token_purchases_count ?? 0),
            'referrals_count' => (int) ($user->referrals_made_count ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function jobPayload(WorkLog $log): array
    {
        $status = 'logged';
        if ($log->hidden_at) {
            $status = 'removed';
        } elseif ($log->flagged_at) {
            $status = 'flagged';
        } elseif ($log->review) {
            $status = 'reviewed';
        } elseif ($log->review_requested_at) {
            $status = 'awaiting';
        }

        return [
            'id' => $log->id,
            'uid' => $log->uid,
            'description' => $log->description,
            'client_name' => $log->client_name,
            'category' => $log->job_category,
            'worked_on' => $log->worked_on?->toDateString(),
            'worked_on_label' => $log->worked_on?->format('j M Y'),
            'flagged' => $log->flagged_at !== null,
            'hidden' => $log->hidden_at !== null,
            'removed' => $log->removed_at !== null,
            'status' => $status,
            'has_review' => $log->review !== null,
        ];
    }

    private function revenueLabel(int $amount): string
    {
        if ($amount >= 1000) {
            return '₦'.NumberFormat::compact($amount);
        }

        return NumberFormat::naira($amount, false);
    }

    private function planLabel(User $user): string
    {
        if ($user->plan === 'annual' || ($user->annual_expires_at && $user->annual_expires_at->isFuture())) {
            return 'Annual';
        }

        if ($user->plan === 'payg' || (int) $user->token_balance > 0) {
            return 'Pay-as-you-go';
        }

        return 'Free';
    }

    /**
     * @return array{status: string, user?: User}
     */
    private function suspendThroughApproval(User $actor, User $user, string $reason, ApprovalService $approvals): array
    {
        abort_unless($user->isRegularUser(), 403);

        $old = ['suspended_at' => $user->suspended_at?->toIso8601String()];

        $result = $approvals->run(
            $actor,
            'users.suspend',
            $user,
            $reason,
            [
                'user_id' => $user->id,
                'reason' => $reason,
                'old' => $old,
                'new' => ['suspended_at' => now()->toIso8601String(), 'reason' => $reason],
            ],
            function () use ($user, $reason, $old, $actor) {
                $user->forceFill([
                    'suspended_at' => now(),
                    'suspension_reason' => $reason,
                ])->save();

                AdminAudit::record(
                    'users.suspended',
                    "{$actor->name} suspended {$user->email}: {$reason}",
                    $user,
                    $old,
                    ['suspended_at' => $user->suspended_at?->toIso8601String(), 'reason' => $reason],
                );

                $this->forgetSessions($user);
            },
        );

        if (($result['status'] ?? '') === 'executed') {
            $result['user'] = $user->fresh();
        }

        return $result;
    }

    private function forgetSessions(User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->delete();
    }
}
