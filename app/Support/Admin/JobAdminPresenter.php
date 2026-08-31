<?php

namespace App\Support\Admin;

use App\Enums\StaffStatus;
use App\Models\User;
use App\Models\WorkLog;
use App\Models\WorkLogMedia;
use App\Support\ReviewInvite;
use Illuminate\Support\Str;

class JobAdminPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function listRow(WorkLog $log): array
    {
        $user = $log->user;

        return [
            'id' => $log->id,
            'uid' => $log->uid,
            'description' => $log->description,
            'client_name' => $log->client_name,
            'category' => $log->job_category,
            'worked_on' => $log->worked_on?->format('j M Y'),
            'created_at' => self::stamp($log->created_at),
            'created_iso' => $log->created_at?->toIso8601String(),
            'backdated_days' => $log->worked_on && $log->created_at
                ? $log->created_at->startOfDay()->diffInDays($log->worked_on)
                : 0,
            'flagged' => $log->flagged_at !== null,
            'flag_reason' => $log->flag_reason,
            'hidden' => $log->hidden_at !== null,
            'removed' => $log->removed_at !== null,
            'referred' => $log->referred_at !== null,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->displayBusinessName(),
                'email' => $user->email,
                'trade' => $user->trade,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function panel(WorkLog $log, User $actor): array
    {
        $log->loadMissing([
            'user:id,name,email,first_name,business_name,slug,trade,whatsapp,state,lga,suspended_at',
            'media',
            'review:id,uid,work_log_id,rating,comment,client_display_name,submitted_at,hidden_at,removed_at',
            'patrolCase:id,work_log_id,kind,status,severity,flagged_at',
            'referredTo:id,name,email',
            'referredBy:id,name,email',
        ]);

        return [
            'record' => self::record($log, $actor),
            'can' => self::abilities($actor),
            'staff' => self::staffOptions(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function record(WorkLog $log, User $actor): array
    {
        $user = $log->user;
        $review = $log->review;
        $patrol = $log->relationLoaded('patrolCase') ? $log->patrolCase : $log->patrolCase()->first();
        $tz = config('app.display_timezone');

        $visibility = 'public';
        if ($log->removed_at) {
            $visibility = 'removed';
        } elseif ($log->hidden_at) {
            $visibility = 'hidden';
        }

        $requestStatus = 'not_requested';
        $requestLabel = 'No review requested';
        if ($review) {
            $requestStatus = 'completed';
            $requestLabel = 'Review received';
        } elseif ($log->review_requested_at && $log->review_token_expires_at?->isPast()) {
            $requestStatus = 'no_response';
            $requestLabel = 'Requested — no response';
        } elseif ($log->review_requested_at) {
            $requestStatus = 'requested';
            $requestLabel = 'Review requested';
        }

        $whatsapp = ReviewInvite::normalizeWhatsapp($user?->whatsapp);

        return [
            ...self::listRow($log),
            'slug' => $log->slug,
            'description_full' => $log->description,
            'worked_on_label' => $log->worked_on?->format('j M Y'),
            'location' => self::location($log),
            'client_whatsapp' => $log->client_whatsapp,
            'category' => $log->job_category,
            'subcategory' => $log->job_subcategory,
            'visibility' => $visibility,
            'visibility_label' => match ($visibility) {
                'hidden' => 'Hidden from public page',
                'removed' => 'Removed from public page',
                default => 'Public',
            },
            'hidden_reason' => $log->hidden_reason,
            'flagged_at_label' => self::stamp($log->flagged_at),
            'hidden_at_label' => self::stamp($log->hidden_at),
            'removed_at_label' => self::stamp($log->removed_at),
            'updated_at' => self::stamp($log->updated_at),
            'review_request' => [
                'status' => $requestStatus,
                'label' => $requestLabel,
                'requested_at' => self::stamp($log->review_requested_at),
                'expires_at' => self::stamp($log->review_token_expires_at),
            ],
            'review' => $review ? [
                'uid' => $review->uid,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'client' => $review->client_display_name,
                'submitted_at' => self::stamp($review->submitted_at),
            ] : null,
            'media' => $log->media
                ->map(fn (WorkLogMedia $item) => [
                    'id' => $item->id,
                    'kind' => $item->kind,
                    'url' => $item->thumbUrl(900),
                    'preview_url' => $item->previewUrl(1400),
                ])
                ->values()
                ->all(),
            'artisan' => $user ? [
                'id' => $user->id,
                'name' => $user->displayBusinessName(),
                'email' => $user->email,
                'trade' => $user->trade,
                'first_name' => $user->first_name,
                'whatsapp' => $user->whatsapp,
                'whatsapp_url' => $whatsapp ? 'https://wa.me/'.$whatsapp : null,
                'suspended' => $user->suspended_at !== null,
                'public_url' => $user->publicUrl(),
                'user_url' => $actor->canDo('admin.users.view')
                    ? route('admin.users.show', $user)
                    : null,
            ] : null,
            'public_url' => $log->publicUrl(),
            'patrol' => $patrol && $actor->canDo('patrol.view') ? [
                'id' => $patrol->id,
                'status' => $patrol->status,
                'status_label' => Str::headline((string) $patrol->status),
                'severity' => $patrol->severity,
                'url' => route('admin.patrol.show', $patrol),
            ] : null,
            'referral' => $log->referred_at ? [
                'assignee_id' => $log->referred_to_user_id,
                'assignee_name' => $log->referredTo?->name ?: $log->referredTo?->email,
                'note' => $log->referred_note,
                'referred_at' => self::stamp($log->referred_at),
                'referred_by' => $log->referredBy?->name ?: $log->referredBy?->email,
            ] : null,
            'timezone' => $tz,
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function abilities(User $actor): array
    {
        $manage = $actor->canDo('admin.content.manage');

        return [
            'manage' => $manage,
            'flag' => $manage,
            'refer' => $manage,
            'hide' => $manage,
            'remove' => $manage,
            'message' => $manage || $actor->canDo('admin.messaging.manage'),
            'view_users' => $actor->canDo('admin.users.view'),
            'view_patrol' => $actor->canDo('patrol.view'),
            'suspend_user' => $actor->canDo('admin.users.manage'),
        ];
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public static function staffOptions(): array
    {
        return User::query()
            ->staff()
            ->where('staff_status', StaffStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name ?: $user->email,
            ])
            ->values()
            ->all();
    }

    private static function location(WorkLog $log): ?string
    {
        $parts = array_values(array_filter([
            $log->service_city,
            $log->service_lga,
            $log->service_state,
        ], fn ($value) => filled($value)));

        return $parts === [] ? null : implode(', ', $parts);
    }

    private static function stamp(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value->timezone(config('app.display_timezone'))->format('j M Y · g:ia');
    }
}
