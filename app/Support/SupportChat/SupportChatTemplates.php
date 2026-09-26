<?php

namespace App\Support\SupportChat;

use App\Models\SupportCannedReply;
use Illuminate\Support\Facades\Cache;

class SupportChatTemplates
{
    public const SCOPE_PERSONAL = 'personal';

    public const SCOPE_TEAM = 'team';

    public const MOMENT_OPEN = 'open';

    public const MOMENT_CLOSE = 'close';

    public const MOMENT_REVIEW = 'review';

    public const MOMENT_GENERAL = 'general';

    /**
     * @return list<string>
     */
    public static function moments(): array
    {
        return [
            self::MOMENT_OPEN,
            self::MOMENT_CLOSE,
            self::MOMENT_REVIEW,
            self::MOMENT_GENERAL,
        ];
    }

    /**
     * @return list<array{key: string, label: string, hint: string}>
     */
    public static function momentOptions(): array
    {
        return [
            ['key' => self::MOMENT_OPEN, 'label' => 'Open a chat', 'hint' => 'First reply when you pick up a conversation'],
            ['key' => self::MOMENT_CLOSE, 'label' => 'Close a chat', 'hint' => 'Wrap up before you mark it resolved'],
            ['key' => self::MOMENT_REVIEW, 'label' => 'Ask for a review', 'hint' => 'Nudge the artisan to send their client the review link'],
            ['key' => self::MOMENT_GENERAL, 'label' => 'General', 'hint' => 'Anything else you say often'],
        ];
    }

    /**
     * @return list<array{title: string, body: string, moment: string, topic_key: string|null}>
     */
    public static function defaults(): array
    {
        return [
            // Open
            [
                'title' => 'Greeting',
                'moment' => self::MOMENT_OPEN,
                'topic_key' => null,
                'body' => "Hi {first_name}, this is {agent} from Kraftrack. I've picked this up — how can I help?",
            ],
            [
                'title' => 'Looking into it',
                'moment' => self::MOMENT_OPEN,
                'topic_key' => null,
                'body' => "Hi {first_name}, thanks for writing in. I'm looking into this now and I'll come back with a clear next step.",
            ],
            [
                'title' => 'Sorry for the wait',
                'moment' => self::MOMENT_OPEN,
                'topic_key' => null,
                'body' => "Hi {first_name}, sorry you had to wait — I'm {agent} and I'm on this with you now. What's the main thing you need help with?",
            ],
            [
                'title' => 'Billing — intro',
                'moment' => self::MOMENT_OPEN,
                'topic_key' => 'billing',
                'body' => "Hi {first_name}, I can help with credits and billing. Tell me what you see on your side (a charge, a balance, or a failed payment) and I'll check it.",
            ],
            [
                'title' => 'Public page — intro',
                'moment' => self::MOMENT_OPEN,
                'topic_key' => 'page',
                'body' => "Hi {first_name}, happy to help with your public page. Is this about how it looks, what shows up, or sharing the link with clients?",
            ],
            [
                'title' => 'Work log — intro',
                'moment' => self::MOMENT_OPEN,
                'topic_key' => 'work-log',
                'body' => "Hi {first_name}, I can help with a job you logged. Share the job or a screenshot and what you'd like changed.",
            ],
            [
                'title' => 'Review request — intro',
                'moment' => self::MOMENT_OPEN,
                'topic_key' => 'reviews',
                'body' => "Hi {first_name}, I can walk you through requesting a client review. Do you already have the finished job logged, or do you need help creating it first?",
            ],

            // Close
            [
                'title' => 'Resolved',
                'moment' => self::MOMENT_CLOSE,
                'topic_key' => null,
                'body' => "I've marked this as resolved. If anything else comes up, reply here and we'll jump back in.",
            ],
            [
                'title' => 'Anything else?',
                'moment' => self::MOMENT_CLOSE,
                'topic_key' => null,
                'body' => 'Glad we got this sorted. Anything else you need before I close the chat?',
            ],
            [
                'title' => 'Closing for now',
                'moment' => self::MOMENT_CLOSE,
                'topic_key' => null,
                'body' => "I'll close this chat for now. Reply anytime if you need us again — we keep the history here.",
            ],
            [
                'title' => 'Billing sorted',
                'moment' => self::MOMENT_CLOSE,
                'topic_key' => 'billing',
                'body' => "Billing looks sorted on our side. If a receipt or balance still looks off on yours, reply with a screenshot and I'll take another look.",
            ],

            // Review
            [
                'title' => 'Send a review link',
                'moment' => self::MOMENT_REVIEW,
                'topic_key' => 'reviews',
                'body' => 'The fastest way to grow your public page is a real client review. Open the finished job, tap Request a review, and send the WhatsApp message. Your client can leave stars in under a minute — no account needed.',
            ],
            [
                'title' => 'After the job is done',
                'moment' => self::MOMENT_REVIEW,
                'topic_key' => 'reviews',
                'body' => "If the work is already done, send your client the review link from that job. Those reviews sit next to the work on your public page, and you can't write them yourself — that's what makes them count.",
            ],
            [
                'title' => 'Client won’t open the link',
                'moment' => self::MOMENT_REVIEW,
                'topic_key' => 'reviews',
                'body' => "If your client hasn’t opened the link yet, resend the WhatsApp message from the job. Keep it short — they only need stars and a quick comment. No account required.",
            ],
            [
                'title' => 'Why reviews matter',
                'moment' => self::MOMENT_REVIEW,
                'topic_key' => 'reviews',
                'body' => 'Clients trust finished work with real reviews next to it — not claims. One solid review on a logged job is worth more than a long about section.',
            ],

            // General
            [
                'title' => 'Give me a moment',
                'moment' => self::MOMENT_GENERAL,
                'topic_key' => null,
                'body' => "Give me a moment to check that — I'll be right back.",
            ],
            [
                'title' => 'Need a bit more',
                'moment' => self::MOMENT_GENERAL,
                'topic_key' => null,
                'body' => 'Thanks. Could you share a screenshot or the job this is about so I can help faster?',
            ],
            [
                'title' => 'Confirm I understood',
                'moment' => self::MOMENT_GENERAL,
                'topic_key' => null,
                'body' => "Just to make sure I've got this right: you're saying ___ — is that correct?",
            ],
            [
                'title' => 'Here’s what to do next',
                'moment' => self::MOMENT_GENERAL,
                'topic_key' => null,
                'body' => "Here's the next step on your side:\n1. …\n2. …\nReply here when that's done and I'll confirm.",
            ],
            [
                'title' => 'Outside our control',
                'moment' => self::MOMENT_GENERAL,
                'topic_key' => null,
                'body' => "That's outside what we can change from support, but here's the best path: … If you hit a wall, reply and we'll see what else we can do.",
            ],
            [
                'title' => 'Account / login tip',
                'moment' => self::MOMENT_GENERAL,
                'topic_key' => null,
                'body' => 'Try signing out, then back in with the same email. If it still fails, tell me the exact message you see (or send a screenshot).',
            ],
            [
                'title' => 'Credits explainer',
                'moment' => self::MOMENT_GENERAL,
                'topic_key' => 'billing',
                'body' => 'Credits are used for review requests and related actions on Kraftrack. Your balance is on your account — if a purchase didn’t land, share the time and amount and I’ll check.',
            ],
            [
                'title' => 'Sharing your page',
                'moment' => self::MOMENT_GENERAL,
                'topic_key' => 'page',
                'body' => 'Your public page link is the best thing to send clients. From your dashboard, open your page and copy the URL — that’s what you share on WhatsApp or your bio.',
            ],
        ];
    }

    public static function ensure(): void
    {
        foreach (self::defaults() as $row) {
            $existing = SupportCannedReply::withTrashed()
                ->where('is_system', true)
                ->where('moment', $row['moment'])
                ->where('title', $row['title'])
                ->first();

            // Super Admin soft-deleted this built-in — leave it gone.
            if ($existing?->trashed()) {
                continue;
            }

            if ($existing) {
                // Do not overwrite SA edits to built-in copy.
                continue;
            }

            SupportCannedReply::query()->create([
                'is_system' => true,
                'moment' => $row['moment'],
                'title' => $row['title'],
                'user_id' => null,
                'scope' => self::SCOPE_TEAM,
                'body' => $row['body'],
                'topic_key' => $row['topic_key'],
            ]);
        }

        Cache::forget('support.templates.ready');
        Cache::forget('support.templates.ready.v2');
    }
}
