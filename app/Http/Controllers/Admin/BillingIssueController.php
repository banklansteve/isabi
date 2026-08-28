<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolveBillingIssueRequest;
use App\Models\BillingIssue;
use App\Support\Admin\AdminAudit;
use App\Support\Admin\AdminResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BillingIssueController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->canDo('admin.billing_issues.manage') || $user?->isSuperAdmin(), 403);

        $query = BillingIssue::query()
            ->with(['user:id,uid,name,email,business_name', 'assignee:id,name', 'purchase:id,reference,status,price'])
            ->latest('id');

        if (! $user->isSuperAdmin()) {
            $query->where(function ($inner) use ($user) {
                $inner->where('assigned_to_user_id', $user->id)
                    ->orWhereNull('assigned_to_user_id');
            })->whereIn('status', [BillingIssue::STATUS_OPEN, BillingIssue::STATUS_ASSIGNED]);
        }

        $issues = $query->limit(200)->get()->map(fn (BillingIssue $issue) => [
            'uid' => $issue->uid,
            'type' => $issue->type,
            'status' => $issue->status,
            'title' => $issue->title,
            'details' => $issue->details,
            'reference' => $issue->reference,
            'amount_label' => $issue->amount_kobo !== null
                ? '₦'.number_format($issue->amount_kobo / 100, 2)
                : null,
            'user' => $issue->user?->only(['id', 'uid', 'name', 'email', 'business_name']),
            'assignee' => $issue->assignee?->only(['id', 'name']),
            'when' => $issue->created_at?->timezone(config('app.display_timezone'))->diffForHumans(),
        ]);

        return Inertia::render('Admin/BillingIssues/Index', [
            'issues' => $issues,
            'is_super' => $user->isSuperAdmin(),
        ]);
    }

    public function claim(Request $request, BillingIssue $issue): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->canDo('admin.billing_issues.manage'), 403);

        $issue->forceFill([
            'assigned_to_user_id' => $request->user()->id,
            'status' => BillingIssue::STATUS_ASSIGNED,
        ])->save();

        AdminAudit::record(
            'billing_issues.claimed',
            "{$request->user()->name} claimed billing issue {$issue->uid}.",
            $issue,
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => 'Assigned to you',
            'message' => 'Work the issue and resolve when done.',
        ]);
    }

    public function resolve(ResolveBillingIssueRequest $request, BillingIssue $issue): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $status = $data['status'] === 'dismissed'
            ? BillingIssue::STATUS_DISMISSED
            : BillingIssue::STATUS_RESOLVED;

        $issue->forceFill([
            'status' => $status,
            'resolved_by_user_id' => $request->user()->id,
            'resolved_at' => now(),
            'resolution_note' => $data['note'],
            'assigned_to_user_id' => $issue->assigned_to_user_id ?? $request->user()->id,
        ])->save();

        AdminAudit::record(
            'billing_issues.'.$status,
            "{$request->user()->name} marked billing issue {$issue->uid} as {$status}.",
            $issue,
            null,
            ['note' => $data['note']],
        );

        return AdminResponse::mutation($request, [
            'type' => 'success',
            'title' => $status === BillingIssue::STATUS_DISMISSED ? 'Dismissed' : 'Resolved',
            'message' => 'The billing issue is closed.',
        ]);
    }

    public static function openIssue(array $attrs): BillingIssue
    {
        return BillingIssue::query()->create([
            'uid' => 'BI'.strtoupper(Str::random(10)),
            'status' => BillingIssue::STATUS_OPEN,
            ...$attrs,
        ]);
    }
}
