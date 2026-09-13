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
            [
                'title' => 'Greeting',
                'moment' => self::MOMENT_OPEN,
                'topic_key' => null,
                'body' => "Hi {name}, this is {agent} from Kraftrack. I've picked this up — how can I help?",
            ],
            [
                'title' => 'Looking into it',
                'moment' => self::MOMENT_OPEN,
                'topic_key' => null,
                'body' => "Hi {name}, thanks for writing in. I'm looking into this now and I'll come back with a clear next step.",
            ],
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
        ];
    }

    public static function ensure(): void
    {
        if (Cache::get('support.templates.ready')) {
            return;
        }

        foreach (self::defaults() as $row) {
            SupportCannedReply::query()->firstOrCreate(
                [
                    'is_system' => true,
                    'moment' => $row['moment'],
                    'title' => $row['title'],
                ],
                [
                    'user_id' => null,
                    'scope' => self::SCOPE_TEAM,
                    'body' => $row['body'],
                    'topic_key' => $row['topic_key'],
                ],
            );
        }

        Cache::put('support.templates.ready', true, now()->addDay());
    }
}
