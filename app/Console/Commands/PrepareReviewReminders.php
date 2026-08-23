<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Console\Command;

/**
 * Surfaces due review nudges. WhatsApp still opens the same click-to-chat way —
 * this command only finds who is due so the artisan can send with one tap.
 */
class PrepareReviewReminders extends Command
{
    protected $signature = 'reviews:prepare-reminders';

    protected $description = 'Find work logs due for a one-shot WhatsApp review reminder';

    public function handle(): int
    {
        $due = WorkLog::query()
            ->with('user')
            ->whereNotNull('review_requested_at')
            ->whereNull('review_reminder_sent_at')
            ->whereDoesntHave('review')
            ->whereHas('user', function ($query) {
                $query->where(function ($inner) {
                    $inner->whereNull('review_reminder_days')
                        ->orWhere('review_reminder_days', '>', 0);
                });
            })
            ->get()
            ->filter(fn (WorkLog $log) => $log->reminderDue());

        $byUser = $due->groupBy('user_id');

        foreach ($byUser as $userId => $logs) {
            /** @var User|null $user */
            $user = $logs->first()?->user;
            if (! $user) {
                continue;
            }

            $this->line(sprintf(
                '%s — %d reminder%s due',
                $user->email,
                $logs->count(),
                $logs->count() === 1 ? '' : 's',
            ));
        }

        $this->info("Due reminders: {$due->count()} across {$byUser->count()} artisan(s).");

        return self::SUCCESS;
    }
}
