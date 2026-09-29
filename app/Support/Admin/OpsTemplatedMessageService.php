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
        $subject = $this->personalize($subject, $artisan);
        $body = $this->personalize($body, $artisan);

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
                'subject' => 'Important notice about your Kraftrack account',
                'body' => "Hi {{first_name}},\n\nWe noticed activity on your account that appears to breach Kraftrack’s community policies.\n\nPlease review our guidelines and correct this promptly. Continued breaches may lead to suspension.\n\nIf you believe this is a mistake, reply to support from Help in the app.\n\n— Kraftrack",
                'whatsapp_body' => "Hi {{first_name}}, it's the Kraftrack team. We need you to review our community policies on your account — please check your email or open the app for details.",
                'editable_keys' => ['body', 'whatsapp_body'],
            ],
            [
                'slug' => 'policy-breach',
                'title' => 'Policy breach notice',
                'category' => 'policy',
                'subject' => 'Your Kraftrack account — policy breach',
                'body' => "Hi {{first_name}},\n\nYour account has been found in breach of Kraftrack policies regarding authentic work and reviews.\n\nWe may hide or remove affected content and take further action if needed.\n\n— Kraftrack Trust & Safety",
                'whatsapp_body' => "Hi {{first_name}}, Kraftrack Trust & Safety here. We've found a policy issue on your account — please check your email for next steps.",
                'editable_keys' => ['body', 'whatsapp_body'],
            ],
            [
                'slug' => 'content-hidden',
                'title' => 'Content hidden',
                'category' => 'moderation',
                'subject' => 'An update about your public page',
                'body' => "Hi {{first_name}},\n\nWe’ve hidden one or more items from your public page while we review them against our policies.\n\nYou’ll be able to see the outcome in your Kraftrack account.\n\n— Kraftrack",
                'whatsapp_body' => "Hi {{first_name}}, we've temporarily hidden some content on your Kraftrack page while we review it. Open the app for details.",
                'editable_keys' => ['body', 'whatsapp_body'],
            ],
            [
                'slug' => 'onboarding-first-job',
                'title' => 'Onboarding — log your first job',
                'category' => 'onboarding',
                'subject' => 'You’re verified — log your first job on Kraftrack',
                'body' => "Hi {{first_name}},\n\nWelcome to Kraftrack. Your account is verified — the next step is logging a finished job so clients can see your work and leave a real review.\n\nIt only takes a minute: open the app → Log a job → add a short subject, date, and optional photo.\n\nWe’re here if you get stuck.\n\n— The Kraftrack team",
                'whatsapp_body' => "Hi {{first_name}}, it's Kraftrack — you're verified! Ready to log your first job so clients can see your work? Open the app and tap Log a job. Need a hand? Reply here.",
                'editable_keys' => ['subject', 'body', 'whatsapp_body'],
            ],
            [
                'slug' => 'onboarding-ask-review',
                'title' => 'Onboarding — ask for a review',
                'category' => 'onboarding',
                'subject' => 'Your jobs look great — ask a client for a review',
                'body' => "Hi {{first_name}},\n\nYou’ve logged work on Kraftrack — nice progress. The next step that grows your page is a real client review.\n\nOpen a finished job → Request a review → send the WhatsApp link to your client. It takes under a minute for them to leave stars and a comment.\n\n— The Kraftrack team",
                'whatsapp_body' => "Hi {{first_name}}, Kraftrack here. You've got jobs logged — next, ask a client for a review from the job page (Request a review → WhatsApp). Happy to walk you through it.",
                'editable_keys' => ['subject', 'body', 'whatsapp_body'],
            ],
            [
                'slug' => 'onboarding-complete-profile',
                'title' => 'Onboarding — complete your profile',
                'category' => 'onboarding',
                'subject' => 'Finish your Kraftrack profile so clients trust you',
                'body' => "Hi {{first_name}},\n\nYour profile is still incomplete, so clients may skip past you. Adding a photo, trade, area, and a short bio makes a big difference.\n\nOpen Profile in the app and fill in the missing bits — it usually takes a few minutes.\n\n— The Kraftrack team",
                'whatsapp_body' => "Hi {{first_name}}, it's Kraftrack. Your profile is still thin — add a photo, trade, and area so clients trust you. Open Profile in the app when you can.",
                'editable_keys' => ['subject', 'body', 'whatsapp_body'],
            ],
            [
                'slug' => 'reengage-login-quiet',
                'title' => 'Re-engage — we’ve missed you',
                'category' => 'reengagement',
                'subject' => 'We’ve missed you on Kraftrack',
                'body' => "Hi {{first_name}},\n\nIt’s been a while since you signed in to Kraftrack. Your public page and reviews are still here when you’re ready.\n\nLog in, check for new quote requests, and keep your work log fresh — we’re happy to help if anything’s unclear.\n\n— The Kraftrack team",
                'whatsapp_body' => "Hi {{first_name}}, it's the Kraftrack team — we've missed you! Anything we can help with to get you back logging jobs and reviews?",
                'editable_keys' => ['subject', 'body', 'whatsapp_body'],
            ],
            [
                'slug' => 'dormant-at-risk',
                'title' => 'Dormant — back to productive use',
                'category' => 'dormant',
                'subject' => 'Your Kraftrack page needs fresh work',
                'body' => "Hi {{first_name}},\n\nYou haven’t logged a job or requested a review in a while. Quiet pages lose trust with clients who are browsing now.\n\nWhen you finish your next job, log it on Kraftrack and send a review link — it keeps {{business_name}} looking active and credible.\n\nWe’re here if you need a refresher.\n\n— The Kraftrack team",
                'whatsapp_body' => "Hi {{first_name}}, Kraftrack here. It's been quiet on your page — when you finish your next job, log it and ask for a review so clients keep trusting {{business_name}}. Need help?",
                'editable_keys' => ['subject', 'body', 'whatsapp_body'],
            ],
            [
                'slug' => 'dormant-never-returned',
                'title' => 'Dormant — never got started',
                'category' => 'dormant',
                'subject' => 'Still here when you’re ready to show your work',
                'body' => "Hi {{first_name}},\n\nYou signed up for Kraftrack but haven’t logged productive work yet. Your account is ready whenever you are.\n\nLog a finished job, then request a client review — that’s the loop that builds a shareable page clients trust.\n\nReply if you’d like a quick walkthrough.\n\n— The Kraftrack team",
                'whatsapp_body' => "Hi {{first_name}}, it's Kraftrack. You signed up but haven't logged work yet — want a quick tip to get your first job and review on your page?",
                'editable_keys' => ['subject', 'body', 'whatsapp_body'],
            ],
        ];

        foreach ($defaults as $row) {
            $existing = OpsMessageTemplate::query()->where('slug', $row['slug'])->first();

            if ($existing) {
                $existing->forceFill([
                    'title' => $row['title'],
                    'category' => $row['category'],
                    'subject' => $row['subject'],
                    'body' => $row['body'],
                    'whatsapp_body' => $row['whatsapp_body'] ?? $existing->whatsapp_body,
                    'editable_keys' => $row['editable_keys'],
                    'is_active' => true,
                ])->save();

                continue;
            }

            OpsMessageTemplate::query()->create([
                'slug' => $row['slug'],
                'uid' => 'OT'.strtoupper(Str::random(10)),
                'title' => $row['title'],
                'category' => $row['category'],
                'subject' => $row['subject'],
                'body' => $row['body'],
                'whatsapp_body' => $row['whatsapp_body'] ?? null,
                'editable_keys' => $row['editable_keys'],
                'is_active' => true,
                'created_by_user_id' => $creator->id,
            ]);
        }
    }

    /**
     * Replace template placeholders for preview / WhatsApp click-to-chat.
     */
    public function personalize(string $text, User $artisan): string
    {
        return app(AnnouncementService::class)->interpolate(
            strtr($text, [
                '{name}' => '{{name}}',
                '{first_name}' => '{{first_name}}',
                '{business_name}' => '{{business_name}}',
            ]),
            $artisan,
        );
    }
}
