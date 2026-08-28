<?php

namespace App\Support\Admin;

use App\Models\AdminAuditLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AdminAuditPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function row(AdminAuditLog $log): array
    {
        $tz = (string) config('app.display_timezone', 'Africa/Lagos');
        $at = $log->created_at?->timezone($tz);

        return [
            'id' => $log->id,
            'action' => $log->action,
            'action_label' => self::actionLabel($log->action),
            'summary' => $log->summary,
            'changes' => self::changes($log->old_values, $log->new_values),
            'actor' => $log->actor ? [
                'id' => $log->actor->id,
                'name' => $log->actor->name ?: $log->actor->email,
                'email' => $log->actor->email,
            ] : null,
            'when' => $at?->format('j M Y, g:i a'),
            'when_full' => $at?->format('l, j F Y \\a\\t g:i a T'),
            'relative' => $log->created_at?->diffForHumans(),
        ];
    }

    public static function actionLabel(string $action): string
    {
        return match ($action) {
            'staff.invited' => 'Sent a staff invite',
            'staff.invite_resent' => 'Resent a staff invite',
            'staff.invite_revoked' => 'Revoked a staff invite',
            'staff.invite_accepted' => 'Staff invite accepted',
            'staff.disabled' => 'Disabled staff access',
            'staff.reinstated' => 'Restored staff access',
            'staff.removed' => 'Removed staff access',
            'staff.promoted' => 'Granted Super Admin',
            'staff.demoted' => 'Removed Super Admin',
            'staff.role_assigned', 'staff.roles_synced' => 'Updated staff roles',
            'staff.role_removed' => 'Removed a staff role',
            'staff.role_created' => 'Created a staff role',
            'staff.role_updated' => 'Updated a staff role',
            'staff.role_destroyed' => 'Deleted a staff role',
            'staff.force_logout' => 'Signed staff out everywhere',
            'staff.password_reset' => 'Sent a staff password reset',
            'staff.messaged', 'staff.announcement_sent' => 'Messaged staff',
            'staff.bulk_messaged' => 'Sent a bulk staff message',
            'users.suspended' => 'Suspended an artisan',
            'users.reinstated' => 'Reinstated an artisan',
            'users.verified' => 'Verified an artisan',
            'users.unverified' => 'Removed artisan verification',
            'users.profile_updated' => 'Edited an artisan profile',
            'users.plan_changed' => 'Changed an artisan plan',
            'users.impersonated' => 'Signed in as an artisan',
            'users.force_logout' => 'Signed an artisan out',
            'users.password_reset' => 'Sent an artisan password reset',
            'users.deleted' => 'Deleted an artisan',
            'credits.adjusted' => 'Adjusted token balance',
            'jobs.flagged' => 'Flagged a job',
            'jobs.unflagged' => 'Cleared a job flag',
            'jobs.updated' => 'Edited a job',
            'jobs.hidden' => 'Hid a job',
            'jobs.unhidden' => 'Unhid a job',
            'jobs.referred' => 'Referred a job',
            'jobs.artisan_messaged' => 'Messaged an artisan about a job',
            'reviews.flagged' => 'Flagged a review',
            'reviews.unflagged' => 'Cleared a review flag',
            'reviews.hidden' => 'Hid a review',
            'support.replied' => 'Replied to a support ticket',
            'support.resolved' => 'Closed a support chat',
            'support.claimed' => 'Started a support chat',
            'support.took_over' => 'Took over a support chat',
            'support.assigned' => 'Assigned a support chat',
            'support.reopened' => 'Reopened a support chat',
            'staff.shift_updated' => 'Updated a staff shift',
            'pricing.updated' => 'Published pricing',
            'settings.updated' => 'Updated application settings',
            'discipline.case_opened' => 'Opened a disciplinary case',
            'discipline.note_added' => 'Added a case note',
            'discipline.evidence_attached' => 'Attached case evidence',
            'discipline.action_issued' => 'Recorded a formal disciplinary action',
            'discipline.acknowledged' => 'Staff acknowledged a notice',
            'discipline.response_submitted' => 'Staff responded to a notice',
            'discipline.appeal_raised' => 'An appeal was raised',
            'discipline.appeal_reviewed' => 'Reviewed a disciplinary appeal',
            'discipline.case_archived' => 'Archived a disciplinary case',
            'discipline.template_updated' => 'Updated a letter template',
            'announcement.sent' => 'Sent an announcement',
            'announcement.scheduled' => 'Scheduled an announcement',
            'announcement.drafted' => 'Saved an announcement draft',
            'announcement.cancelled' => 'Cancelled an announcement',
            'patrol.dismissed' => 'Dismissed a patrol case',
            'patrol.removed' => 'Removed a patrol entry',
            'patrol.hidden' => 'Hid a review from patrol',
            'patrol.note_added' => 'Added a patrol note',
            'patrol.handoff_suspend' => 'Handed off a patrol case for suspension',
            'patrol.recommendation_rejected' => 'Rejected a patrol recommendation',
            'patrol.rule_detached' => 'Detached a patrol rule',
            'patrol.status_changed' => 'Updated a patrol case status',
            default => str_starts_with($action, 'patrol.recommended_')
                ? 'Recommended '.Str::of(Str::after($action, 'patrol.recommended_'))->replace('_', ' ')->headline().' on a patrol case'
                : Str::of($action)->replace(['.', '_'], ' ')->headline()->toString(),
        };
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     * @return list<array{label: string, from: string, to: string}>
     */
    public static function changes(?array $old, ?array $new): array
    {
        $old = $old ?? [];
        $new = $new ?? [];
        $keys = array_values(array_unique([...array_keys($old), ...array_keys($new)]));
        $rows = [];

        foreach ($keys as $key) {
            if (in_array($key, ['password', 'remember_token', 'user_agent', 'note_id'], true)) {
                continue;
            }

            $from = $old[$key] ?? null;
            $to = $new[$key] ?? null;

            if (self::same($from, $to)) {
                continue;
            }

            $rows[] = [
                'label' => self::fieldLabel((string) $key),
                'from' => self::formatValue($from),
                'to' => self::formatValue($to),
            ];
        }

        return $rows;
    }

    private static function fieldLabel(string $key): string
    {
        return match ($key) {
            'staff_status' => 'Access status',
            'suspended_at' => 'Suspended at',
            'token_balance' => 'Token balance',
            'flagged_at' => 'Flagged at',
            'hidden_at' => 'Hidden at',
            'flag_reason' => 'Flag reason',
            'hidden_reason' => 'Hidden because',
            'work_log_hidden_at' => 'Job hidden at',
            'review_hidden_at' => 'Review hidden at',
            'ip_address' => 'IP address',
            default => Str::of($key)->replace(['.', '_'], ' ')->headline()->toString(),
        };
    }

    private static function same(mixed $left, mixed $right): bool
    {
        return json_encode($left) === json_encode($right);
    }

    private static function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            return self::formatString($value);
        }

        if (is_array($value)) {
            return self::formatArray($value);
        }

        return (string) $value;
    }

    private static function formatString(string $value): string
    {
        $tz = (string) config('app.display_timezone', 'Africa/Lagos');

        if (preg_match('/^\d{4}-\d{2}-\d{2}T/', $value) === 1) {
            try {
                return Carbon::parse($value)->timezone($tz)->format('j M Y, g:i a');
            } catch (\Throwable) {
                return $value;
            }
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            try {
                return Carbon::parse($value, $tz)->format('j M Y');
            } catch (\Throwable) {
                return $value;
            }
        }

        $labelled = config('patrol.statuses.'.$value)
            ?? config('patrol.hidden_reasons.'.$value);

        if (is_string($labelled) && $labelled !== '') {
            return $labelled;
        }

        return match ($value) {
            'active' => 'Active',
            'suspended' => 'Disabled',
            'invited' => 'Invited',
            'resolved' => 'Resolved',
            default => $value,
        };
    }

    /**
     * @param  array<int|string, mixed>  $value
     */
    private static function formatArray(array $value): string
    {
        if ($value === []) {
            return '—';
        }

        if (array_is_list($value) && collect($value)->every(fn ($item) => is_scalar($item) || $item === null)) {
            $parts = collect($value)
                ->map(fn ($item) => self::formatValue($item))
                ->filter(fn ($item) => $item !== '—')
                ->values();

            return $parts->isEmpty() ? '—' : $parts->join(', ');
        }

        $parts = [];

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $nested = self::formatArray($item);
                $parts[] = self::fieldLabel((string) $key).': '.$nested;

                continue;
            }

            $parts[] = self::fieldLabel((string) $key).': '.self::formatValue($item);
        }

        return implode(' · ', $parts);
    }
}
