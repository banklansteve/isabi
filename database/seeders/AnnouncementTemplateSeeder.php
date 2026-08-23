<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\AnnouncementTemplate;
use Illuminate\Database\Seeder;

class AnnouncementTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'audience' => Announcement::AUDIENCE_USERS,
                'name' => 'Welcome',
                'slug' => 'users-welcome',
                'subject' => 'Welcome to Isabi',
                'body' => "Hi {{first_name}}, your page is live.\n\nLog your first finished job so clients can review the work — not a claim. That’s how Isabi turns real jobs into a track record you can share.",
                'channels' => [Announcement::CHANNEL_IN_APP, Announcement::CHANNEL_EMAIL],
            ],
            [
                'audience' => Announcement::AUDIENCE_USERS,
                'name' => 'Annual plan expires in 7 days',
                'slug' => 'users-renewal-7',
                'subject' => 'Your annual plan expires in 7 days',
                'body' => "Hi {{first_name}}, your annual unlock ends in a week.\n\nRenew to keep sending review requests without buying credit packs. Your page and existing reviews stay put either way.",
                'channels' => [Announcement::CHANNEL_IN_APP, Announcement::CHANNEL_EMAIL, Announcement::CHANNEL_WHATSAPP],
            ],
            [
                'audience' => Announcement::AUDIENCE_USERS,
                'name' => 'Referral milestone',
                'slug' => 'users-referral-milestone',
                'subject' => 'You earned referral credits',
                'body' => "Hi {{first_name}}, someone signed up with your link.\n\nThose credits are on your account now — they don’t expire. Keep sharing your page when a job is done well.",
                'channels' => [Announcement::CHANNEL_IN_APP, Announcement::CHANNEL_EMAIL],
            ],
            [
                'audience' => Announcement::AUDIENCE_STAFF,
                'name' => 'Ops briefing',
                'slug' => 'staff-ops-briefing',
                'subject' => 'Ops briefing',
                'body' => "A short note for the operations team.\n\nCheck the dashboard for anything that needs a look today — flagged reviews, support tickets, and scheduled announcements.",
                'channels' => [Announcement::CHANNEL_IN_APP, Announcement::CHANNEL_EMAIL],
            ],
            [
                'audience' => Announcement::AUDIENCE_STAFF,
                'name' => 'Action needed',
                'slug' => 'staff-incident',
                'subject' => 'Action needed in admin',
                'body' => 'Something needs a look in the admin console. Open the relevant queue (support, reviews, or users) and leave a note on the audit log when you act.',
                'channels' => [Announcement::CHANNEL_IN_APP, Announcement::CHANNEL_EMAIL],
            ],
        ];

        foreach ($templates as $template) {
            AnnouncementTemplate::query()->updateOrCreate(
                ['slug' => $template['slug']],
                [
                    ...$template,
                    'is_system' => true,
                ],
            );
        }
    }
}
