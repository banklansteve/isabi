<?php

namespace App\Support\SupportChat;

use App\Enums\StaffStatus;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SupportConversationService
{
    public function __construct(
        private readonly SupportPresence $presence,
        private readonly SupportAttachmentService $attachments,
    ) {}

    public function latestForCustomer(User $user): ?SupportTicket
    {
        return SupportTicket::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();
    }

    public function activeForCustomer(User $user): ?SupportTicket
    {
        return SupportTicket::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_PENDING, SupportTicket::STATUS_NEW])
            ->latest('id')
            ->first();
    }

    /**
     * @param  array{body?: string, topic_key?: string|null, attachment?: UploadedFile|null}  $input
     */
    public function customerMessage(User $user, array $input): SupportTicket
    {
        $topic = $this->normalizeTopic($input['topic_key'] ?? null);
        $body = trim((string) ($input['body'] ?? ''));
        $file = $input['attachment'] ?? null;

        $ticket = DB::transaction(function () use ($user, $topic, $body, $file) {
            $ticket = $this->activeForCustomer($user) ?? $this->latestForCustomer($user);

            if (! $ticket) {
                $ticket = SupportTicket::query()->create([
                    'user_id' => $user->id,
                    'subject' => $this->subjectFrom($body, $topic),
                    'topic_key' => $topic,
                    'tags' => $topic ? [$topic] : [],
                    'status' => SupportTicket::STATUS_NEW,
                    'last_reply_at' => now(),
                    'last_customer_message_at' => now(),
                ]);
            } elseif ($ticket->status === SupportTicket::STATUS_RESOLVED) {
                $this->reopen($ticket, $topic, broadcast: false);
            } elseif ($topic && ! $ticket->topic_key) {
                $ticket->forceFill([
                    'topic_key' => $topic,
                    'tags' => $this->mergeTags($ticket, $topic),
                ])->save();
            }

            $attachment = $file instanceof UploadedFile
                ? $this->attachments->store($file, $ticket->id)
                : null;

            SupportTicketMessage::query()->create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'is_staff' => false,
                'kind' => SupportTicketMessage::KIND_MESSAGE,
                'body' => $body,
                'attachment_disk' => $attachment['disk'] ?? null,
                'attachment_path' => $attachment['path'] ?? null,
                'attachment_url' => $attachment['url'] ?? null,
                'attachment_name' => $attachment['name'] ?? null,
                'attachment_mime' => $attachment['mime'] ?? null,
                'attachment_size' => $attachment['size'] ?? null,
            ]);

            $ticket->forceFill([
                'status' => $ticket->assigned_to_user_id
                    ? SupportTicket::STATUS_OPEN
                    : SupportTicket::STATUS_NEW,
                'last_reply_at' => now(),
                'last_customer_message_at' => now(),
                'resolved_at' => null,
                'subject' => $ticket->subject ?: $this->subjectFrom($body, $topic),
            ])->save();

            $this->route($ticket->fresh());

            return $ticket->fresh() ?? $ticket;
        });

        app(\App\Support\Realtime\Realtime::class)->conversation($ticket);

        return $ticket;
    }

    /**
     * @param  array{body?: string, attachment?: UploadedFile|null, is_close_message?: bool}  $input
     */
    public function staffReply(User $staff, SupportTicket $ticket, array $input): SupportTicketMessage
    {
        $body = trim((string) ($input['body'] ?? ''));
        $file = $input['attachment'] ?? null;
        $isCloseMessage = ! empty($input['is_close_message']);

        $attachment = $file instanceof UploadedFile
            ? $this->attachments->store($file, $ticket->id)
            : null;

        $message = SupportTicketMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $staff->id,
            'is_staff' => true,
            'kind' => SupportTicketMessage::KIND_MESSAGE,
            'body' => $body,
            'attachment_disk' => $attachment['disk'] ?? null,
            'attachment_path' => $attachment['path'] ?? null,
            'attachment_url' => $attachment['url'] ?? null,
            'attachment_name' => $attachment['name'] ?? null,
            'attachment_mime' => $attachment['mime'] ?? null,
            'attachment_size' => $attachment['size'] ?? null,
        ]);

        $ticket->forceFill([
            'status' => SupportTicket::STATUS_PENDING,
            'assigned_to_user_id' => $ticket->assigned_to_user_id ?? $staff->id,
            'assigned_at' => $ticket->assigned_at ?? now(),
            'last_reply_at' => now(),
            'last_staff_message_at' => now(),
            'first_response_at' => $ticket->first_response_at ?? now(),
            'staff_last_read_at' => now(),
            'resolved_at' => null,
            'close_outcome' => null,
            'close_message_sent_at' => $isCloseMessage ? now() : $ticket->close_message_sent_at,
        ])->save();

        app(\App\Support\Realtime\Realtime::class)->conversation($ticket->fresh() ?? $ticket);

        return $message;
    }

    public function addNote(User $staff, SupportTicket $ticket, string $body): SupportTicketMessage
    {
        $note = SupportTicketMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $staff->id,
            'is_staff' => true,
            'kind' => SupportTicketMessage::KIND_NOTE,
            'body' => trim($body),
        ]);

        app(\App\Support\Realtime\Realtime::class)->conversation($ticket, notifyCustomer: false);

        return $note;
    }

    public function resolve(SupportTicket $ticket, string $outcome = SupportTicket::OUTCOME_COMPLETED): void
    {
        $abandoned = $outcome === SupportTicket::OUTCOME_ABANDONED;

        $ticket->forceFill([
            'status' => $abandoned ? SupportTicket::STATUS_ABANDONED : SupportTicket::STATUS_RESOLVED,
            'close_outcome' => $abandoned ? SupportTicket::OUTCOME_ABANDONED : SupportTicket::OUTCOME_COMPLETED,
            'resolved_at' => now(),
            'last_reply_at' => now(),
        ])->save();

        app(\App\Support\Realtime\Realtime::class)->conversation($ticket);
    }

    public function reopen(SupportTicket $ticket, ?string $topic = null, bool $broadcast = true): void
    {
        $ticket->forceFill([
            'status' => SupportTicket::STATUS_NEW,
            'resolved_at' => null,
            'close_outcome' => null,
            'close_message_sent_at' => null,
            'csat_score' => null,
            'csat_comment' => null,
            'csat_dismissed_at' => null,
            'topic_key' => $topic ?: $ticket->topic_key,
            'last_reply_at' => now(),
        ])->save();

        $this->route($ticket);

        if ($broadcast) {
            app(\App\Support\Realtime\Realtime::class)->conversation($ticket);
        }
    }

    public function assign(SupportTicket $ticket, ?User $agent): void
    {
        $ticket->forceFill([
            'assigned_to_user_id' => $agent?->id,
            'assigned_at' => $agent ? now() : null,
            'status' => $ticket->isClosed()
                ? $ticket->status
                : ($agent ? SupportTicket::STATUS_OPEN : SupportTicket::STATUS_NEW),
        ])->save();
    }

    public function tag(SupportTicket $ticket, array $tags): void
    {
        $allowed = collect(config('support.starters', []))
            ->pluck('key')
            ->filter(fn ($key) => $key !== 'other')
            ->values()
            ->all();

        $clean = collect($tags)
            ->map(fn ($tag) => $this->normalizeTopic($tag))
            ->filter(fn ($tag) => $tag && in_array($tag, $allowed, true))
            ->unique()
            ->values()
            ->all();

        $ticket->forceFill([
            'tags' => $clean,
            'topic_key' => $ticket->topic_key ?: ($clean[0] ?? null),
        ])->save();

        app(\App\Support\Realtime\Realtime::class)->conversation($ticket, notifyCustomer: false);
    }

    public function submitCsat(SupportTicket $ticket, ?int $score, ?string $comment, bool $dismiss = false): void
    {
        if ($dismiss) {
            $ticket->forceFill(['csat_dismissed_at' => now()])->save();
            app(\App\Support\Realtime\Realtime::class)->conversation($ticket);

            return;
        }

        $ticket->forceFill([
            'csat_score' => $score,
            'csat_comment' => $comment ? str($comment)->limit(500)->toString() : null,
            'csat_dismissed_at' => now(),
        ])->save();

        app(\App\Support\Realtime\Realtime::class)->conversation($ticket);
    }

    public function markCustomerRead(SupportTicket $ticket): void
    {
        $ticket->forceFill(['customer_last_read_at' => now()])->save();
    }

    public function markStaffRead(SupportTicket $ticket): void
    {
        $ticket->forceFill(['staff_last_read_at' => now()])->save();
    }

    public function route(SupportTicket $ticket): void
    {
        if ($ticket->status === SupportTicket::STATUS_RESOLVED) {
            return;
        }

        $ticket->loadMissing('assignedTo');

        $ability = $this->abilityFor($ticket->topic_key);
        $candidates = $this->eligibleStaff($ability);
        $online = $candidates
            ->filter(fn (User $user) => $this->presence->staffOnline($user))
            ->values();

        // Prefer live Customer Support agents. Fall back to the full eligible
        // roster only when nobody with the duty is currently present.
        $pool = $online->isNotEmpty() ? $online : $candidates;

        if ($this->shouldKeepAssignee($ticket, $pool)) {
            if ($ticket->status === SupportTicket::STATUS_NEW) {
                $ticket->forceFill(['status' => SupportTicket::STATUS_OPEN])->save();
            }

            return;
        }

        if ($pool->isEmpty()) {
            // Drop invalid sticky owners (e.g. Super Admin / former support staff)
            // so the chat can sit in Unassigned for the next eligible agent.
            if ($ticket->assigned_to_user_id) {
                $this->assign($ticket, null);
            }

            return;
        }

        $this->assign($ticket, $this->nextRoundRobin($pool, $ability));
    }

    /**
     * Sticky hand-off stays with the current owner only while they are still
     * a sensible recipient — otherwise the customer "next available agent"
     * copy must actually re-route the chat to an ops agent on duty.
     */
    private function shouldKeepAssignee(SupportTicket $ticket, Collection $pool): bool
    {
        $assigneeId = $ticket->assigned_to_user_id;

        if (! $assigneeId) {
            return false;
        }

        if ($pool->contains(fn (User $user) => (int) $user->id === (int) $assigneeId)) {
            return true;
        }

        $assignee = $ticket->assignedTo;

        // Explicit Super Admin takeover: keep while they remain present.
        return $assignee
            && $assignee->isSuperAdmin()
            && $this->presence->staffOnline($assignee);
    }

    public function refreshRouting(SupportTicket $ticket): SupportTicket
    {
        $this->route($ticket);

        return $ticket->fresh() ?? $ticket;
    }

    /**
     * @return Collection<int, User>
     */
    public function eligibleStaff(string $ability): Collection
    {
        $staff = User::query()
            ->staff()
            ->where('staff_status', StaffStatus::Active)
            ->with('staffRoles')
            ->get();

        return $staff
            ->filter(function (User $user) use ($ability) {
                if ($user->isSuperAdmin()) {
                    return false;
                }

                return $user->canDo($ability) || $user->canDo('admin.support.manage');
            })
            ->sortBy('id')
            ->values();
    }

    public function assignableAgents(): Collection
    {
        return $this->eligibleStaff('admin.support.manage')
            ->map(fn (User $user) => [
                'id' => $user->id,
                'uid' => $user->uid,
                'name' => $user->name,
                'online' => $this->presence->staffOnline($user),
            ])
            ->values();
    }

    private function nextRoundRobin(Collection $candidates, string $ability): User
    {
        $ids = $candidates->pluck('id')->values();
        $cursor = (int) Cache::get($this->cursorKey($ability), -1);
        $index = ($cursor + 1) % max(1, $ids->count());
        Cache::put($this->cursorKey($ability), $index, now()->addDay());

        return $candidates->firstWhere('id', $ids[$index]) ?? $candidates[$index];
    }

    private function cursorKey(string $ability): string
    {
        return 'support.route.cursor.'.$ability;
    }

    private function abilityFor(?string $topic): string
    {
        foreach (config('support.starters', []) as $starter) {
            if (($starter['key'] ?? '') === $topic) {
                return (string) ($starter['ability'] ?? 'admin.support.manage');
            }
        }

        return 'admin.support.manage';
    }

    private function normalizeTopic(mixed $topic): ?string
    {
        $key = is_string($topic) ? trim($topic) : '';

        if ($key === '' || $key === 'other') {
            return null;
        }

        $allowed = collect(config('support.starters', []))->pluck('key')->all();

        return in_array($key, $allowed, true) ? $key : null;
    }

    private function mergeTags(SupportTicket $ticket, string $topic): array
    {
        return collect($ticket->tags ?? [])
            ->push($topic)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function subjectFrom(string $body, ?string $topic): string
    {
        if ($body !== '') {
            return str($body)->limit(80)->toString();
        }

        foreach (config('support.starters', []) as $starter) {
            if (($starter['key'] ?? '') === $topic) {
                return (string) $starter['label'];
            }
        }

        return 'Support chat';
    }
}
