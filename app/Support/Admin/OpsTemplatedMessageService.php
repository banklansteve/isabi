<?php

namespace App\Support\Admin;

use App\Models\Announcement;
use App\Models\OpsMessageTemplate;
use App\Models\User;
use App\Support\Staff\AppSettingsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OpsTemplatedMessageService
{
    public function __construct(
        private readonly AnnouncementService $announcements,
        private readonly ApprovalService $approvals,
        private readonly AppSettingsService $settings,
    ) {}

    public function messagingEnabled(): bool
    {
        return $this->settings->boolean('ops.templated_messaging_enabled', true);
    }

    public function requiresSendApproval(User $actor): bool
    {
        if ($actor->isSuperAdmin()) {
            return false;
        }

        return $this->settings->boolean('ops.templated_messaging_require_approval', false)
            || $this->approvals->requiresApproval($actor, 'messages.send');
    }

    /**
     * @return Collection<int, OpsMessageTemplate>
     */
    public function activeTemplates(): Collection
    {
        return OpsMessageTemplate::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('title')
            ->get();
    }

    /**
     * @param  array{subject?: string, body?: string, channels: list<string>}  $overrides
     * @return array{status: string, approval?: \App\Models\AdminApproval, announcement?: Announcement}
     */
    public function send(
        User $actor,
        User $artisan,
        OpsMessageTemplate $template,
        array $overrides,
    ): array {
        abort_unless($artisan->isRegularUser(), 422, 'Messages can only go to site users.');
        abort_unless($this->messagingEnabled() || $actor->isSuperAdmin(), 403, 'Templated messaging is disabled.');

        $editable = collect($template->editable_keys ?? ['body'])->flip();
        $subject = $editable->has('subject') && filled($overrides['subject'] ?? null)
            ? (string) $overrides['subject']
            : $template->subject;
        $body = $editable->has('body') && filled($overrides['body'] ?? null)
            ? (string) $overrides['body']
            : $template->body;

        $channels = array_values(array_intersect(
            $overrides['channels'] ?? [Announcement::CHANNEL_IN_APP],
            [Announcement::CHANNEL_IN_APP, Announcement::CHANNEL_EMAIL],
        ));

        if ($channels === []) {
            throw ValidationException::withMessages([
                'channels' => 'Choose in-app, email, or both.',
            ]);
        }

        $payload = [
            'template_id' => $template->id,
            'user_id' => $artisan->id,
            'subject' => $subject,
            'body' => $body,
            'channels' => $channels,
        ];

        return $this->approvals->run(
            $actor,
            'messages.send',
            $artisan,
            'Templated message: '.$template->title,
            $payload,
            fn () => $this->dispatch($actor, $artisan, $template, $subject, $body, $channels),
        );
    }

    /**
     * @param  list<string>  $channels
     */
    public function dispatch(
        User $actor,
        User $artisan,
        OpsMessageTemplate $template,
        string $subject,
        string $body,
        array $channels,
    ): Announcement {
        $announcement = Announcement::query()->create([
            'announcement_template_id' => null,
            'audience' => Announcement::AUDIENCE_USERS,
            'title' => $template->title,
            'subject' => $subject,
            'body' => $body,
            'channels' => $channels,
            'segment' => ['user_id' => $artisan->id, 'ops_template_uid' => $template->uid],
            'status' => Announcement::STATUS_DRAFT,
            'created_by_user_id' => $actor->id,
        ]);

        $this->announcements->queue($announcement);

        AdminAudit::record(
            'messages.templated_sent',
            "{$actor->name} sent template “{$template->title}” to {$artisan->email}.",
            $artisan,
            null,
            [
                'template' => $template->uid,
                'channels' => $channels,
                'announcement_id' => $announcement->id,
            ],
            $actor,
        );

        return $announcement->fresh() ?? $announcement;
    }

    public function seedDefaults(User $creator): void
    {
        $defaults = [
            [
                'slug' => 'policy-warning',
                'title' => 'Policy warning',
                'category' => 'policy',
                'subject' => 'Important notice about your Isabi account',
                'body' => "Hi {name},\n\nWe noticed activity on your account that appears to breach Isabi’s community policies.\n\nPlease review our guidelines and correct this promptly. Continued breaches may lead to suspension.\n\nIf you believe this is a mistake, reply to support from Help in the app.\n\n— Isabi",
                'editable_keys' => ['body'],
            ],
            [
                'slug' => 'policy-breach',
                'title' => 'Policy breach notice',
                'category' => 'policy',
                'subject' => 'Your Isabi account — policy breach',
                'body' => "Hi {name},\n\nYour account has been found in breach of Isabi policies regarding authentic work and reviews.\n\nWe may hide or remove affected content and take further action if needed.\n\n— Isabi Trust & Safety",
                'editable_keys' => ['body'],
            ],
            [
                'slug' => 'content-hidden',
                'title' => 'Content hidden',
                'category' => 'moderation',
                'subject' => 'An update about your public page',
                'body' => "Hi {name},\n\nWe’ve hidden one or more items from your public page while we review them against our policies.\n\nYou’ll be able to see the outcome in your Isabi account.\n\n— Isabi",
                'editable_keys' => ['body'],
            ],
        ];

        foreach ($defaults as $row) {
            OpsMessageTemplate::query()->firstOrCreate(
                ['slug' => $row['slug']],
                [
                    'uid' => 'OT'.strtoupper(Str::random(10)),
                    'title' => $row['title'],
                    'category' => $row['category'],
                    'subject' => $row['subject'],
                    'body' => $row['body'],
                    'editable_keys' => $row['editable_keys'],
                    'is_active' => true,
                    'created_by_user_id' => $creator->id,
                ],
            );
        }
    }
}
