<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqExpandSeeder extends Seeder
{
    public function run(): void
    {
        $rows = $this->rows();

        foreach ($rows as $row) {
            Faq::query()->updateOrCreate(
                ['question' => $row['question']],
                [
                    ...$row,
                    'is_published' => true,
                ],
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(): array
    {
        return [
            // Getting started
            [
                'question' => 'Is Kraftrack really free to start?',
                'answer' => 'Yes. Creating your page, logging finished jobs, and sharing your public profile are free — no bank card at signup. You only pay if you need more than five client review requests in a month, or extras such as a custom link. Start free, prove your work, upgrade when the volume justifies it.',
                'category' => 'Getting started',
                'sort_order' => 10,
                'is_featured' => true,
                'featured_sort' => 1,
            ],
            [
                'question' => 'Do I need a bank card to sign up?',
                'answer' => 'No. Sign up with your email and a few business details. When you choose to pay later for more review requests or extras, you can use bank transfer, USSD, or card. We never store your card for silent auto-renew — you stay in control of when money leaves your account.',
                'category' => 'Getting started',
                'sort_order' => 20,
                'is_featured' => true,
                'featured_sort' => 2,
            ],
            [
                'question' => 'How do I set up my page for the first time?',
                'answer' => 'After signup, add your trade, service area, WhatsApp number, and a short bio so clients know who you are. Then log your first finished job (description, date, optional photos). When you are ready, share your page link or QR code. New clients see real work and real reviews — not a blank profile.',
                'category' => 'Getting started',
                'sort_order' => 30,
                'is_featured' => true,
                'featured_sort' => 3,
            ],
            [
                'question' => "What if I don't have many jobs yet?",
                'answer' => 'Start with the next job you finish. A short, honest timeline beats an empty page every time. Free accounts are built for starting from zero — you do not need years of history to join. Log carefully, request reviews, and your track record grows with the work.',
                'category' => 'Getting started',
                'sort_order' => 40,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'What trades and services is Kraftrack for?',
                'answer' => 'Kraftrack is built for skilled trades and service artisans across Nigeria — electricians, plumbers, tilers, painters, carpenters, AC technicians, generators, and similar crafts. If you finish jobs for clients and want a shareable proof trail, you are in the right place. Browse the artisans directory to see how others present their work.',
                'category' => 'Getting started',
                'sort_order' => 50,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'Do I need a laptop, or can I use my phone?',
                'answer' => 'Phone-first. Most artisans use mid-range Android phones and WhatsApp as their operating system. Kraftrack is designed for that reality — signup, job logs, review requests, and your public page all work in a mobile browser. A laptop is optional, not required.',
                'category' => 'Getting started',
                'sort_order' => 60,
                'is_featured' => false,
                'featured_sort' => null,
            ],

            // Reviews
            [
                'question' => 'Can I write my own reviews?',
                'answer' => 'No — and that is by design. Only clients can submit a review through a private link tied to a logged job. You cannot write, edit, or approve their words. That honesty is what makes Kraftrack reviews trustworthy compared to self-written testimonials.',
                'category' => 'Reviews',
                'sort_order' => 100,
                'is_featured' => true,
                'featured_sort' => 4,
            ],
            [
                'question' => 'How does a client actually leave a review?',
                'answer' => 'After you log a job, open Request a review and send the private link — usually via WhatsApp with a pre-written message. The client opens it in a browser (no account needed), rates the job, writes a short comment, and can add an optional photo. Most people finish in under a minute.',
                'category' => 'Reviews',
                'sort_order' => 110,
                'is_featured' => true,
                'featured_sort' => 5,
            ],
            [
                'question' => 'What happens after a client submits a review?',
                'answer' => 'The review is attached to that job on your public page. You cannot edit the client’s words. Visitors see stars and comments next to the work you logged, so new clients can judge your track record for themselves.',
                'category' => 'Reviews',
                'sort_order' => 120,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'Can a client leave a review without me logging a job?',
                'answer' => 'No. Reviews are tied to a specific logged job through a unique link. That stops random or bought testimonials from floating free of real work. Log the job first, then request the review.',
                'category' => 'Reviews',
                'sort_order' => 130,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'What if a client leaves a low rating?',
                'answer' => 'Honest feedback is part of a trustworthy page. You cannot delete or rewrite client reviews. Use the experience to improve the next job — many artisans earn stronger ratings over time as their timeline grows. Fabricating five-star praise would defeat the product’s purpose.',
                'category' => 'Reviews',
                'sort_order' => 140,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'How many review requests do I get for free?',
                'answer' => 'Free accounts include a monthly allowance of client review requests (five per month). If you need more, upgrade when you are ready. Job logging and your public page stay available on free — you are not locked out of building proof.',
                'category' => 'Reviews',
                'sort_order' => 150,
                'is_featured' => false,
                'featured_sort' => null,
            ],

            // Referrals
            [
                'question' => 'How do referrals work on Kraftrack?',
                'answer' => 'When a client reviews a job, they can optionally say who told them about you — a vouch chain that shows how trust travels. That context sits with the review so future clients see that your work spreads through real people, not ads alone.',
                'category' => 'Referrals',
                'sort_order' => 200,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'Do I get paid for referring other artisans?',
                'answer' => 'Referral rewards, if offered, are described in your account when a programme is active. The core product value is still your own proof trail — finished jobs and client reviews — whether or not a referral bonus applies.',
                'category' => 'Referrals',
                'sort_order' => 210,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'Can I ask clients to mention who referred them?',
                'answer' => 'Yes. The review form includes an optional “Who told you about this artisan?” field. Mentioning it politely in your WhatsApp message helps, but it should stay optional so clients never feel forced.',
                'category' => 'Referrals',
                'sort_order' => 220,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'Will referral names show publicly?',
                'answer' => 'Referral context is collected with the review to strengthen trust signals. How it appears on your page follows the product’s privacy rules — we do not turn clients into spam contacts. Your WhatsApp remains the contact channel you choose to share.',
                'category' => 'Referrals',
                'sort_order' => 230,
                'is_featured' => false,
                'featured_sort' => null,
            ],

            // Logging jobs
            [
                'question' => 'What should I include when I log a job?',
                'answer' => 'Add a clear description of the finished work, the date, and optional photos or video. Location and client details help you stay organised. Keep descriptions specific (“rewired kitchen sockets, tested breakers”) so future clients understand the craft, not just a vague “job done”.',
                'category' => 'Logging jobs',
                'sort_order' => 300,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'Can I edit a job after I log it?',
                'answer' => 'You can update job details you own — description, media, and related fields — from your work log. You still cannot invent or edit client reviews attached to that job. Reviews remain the client’s words.',
                'category' => 'Logging jobs',
                'sort_order' => 310,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'Should I log every small job?',
                'answer' => 'Log work you are proud to show and happy to request a review for. A steady stream of real jobs builds a stronger page than a few oversized showpieces. Skip nothing that proves skill — including modest residential work.',
                'category' => 'Logging jobs',
                'sort_order' => 320,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'How do photos and media work?',
                'answer' => 'Attach clear photos or short video when you log a job. Good light and before/after shots help clients trust the outcome. Media sits with that job on your timeline so visitors see proof next to reviews, not floating in a gallery with no context.',
                'category' => 'Logging jobs',
                'sort_order' => 330,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'Why do job links use a code instead of a number?',
                'answer' => 'Public job URLs use opaque codes so people cannot guess or scrape sequential database IDs. Share the official link from your account — it stays stable for that job’s review and page entry.',
                'category' => 'Logging jobs',
                'sort_order' => 340,
                'is_featured' => false,
                'featured_sort' => null,
            ],

            // My Page
            [
                'question' => 'What details show up on my public page?',
                'answer' => 'Visitors see your name or business name, trade, area, photo (if you add one), WhatsApp contact, work-log timeline, and client reviews tied to jobs. You choose what you log; client reviews stay as submitted. It is a living track record, not a static brochure.',
                'category' => 'My Page',
                'sort_order' => 400,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'Who can see my contact details?',
                'answer' => 'Your public page shows the WhatsApp number you choose to share so clients can reach you. Private account email and payment details stay off the page. Only publish a number you are comfortable receiving client messages on.',
                'category' => 'My Page',
                'sort_order' => 410,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'Can I change my page link or business name?',
                'answer' => 'Your SEO slug comes from your business name (normalised to a clean URL). Limited changes are supported; old slugs redirect so shared links do not break. Keep the name clients already search for when possible.',
                'category' => 'My Page',
                'sort_order' => 420,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'How should I share my page with clients?',
                'answer' => 'Copy your public page link or QR code and send it on WhatsApp, SMS, or print it for your workshop. After a job, pair the page link with a review request so the client both rates the work and can revisit your timeline later.',
                'category' => 'My Page',
                'sort_order' => 430,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'Will my page help me get found on Google?',
                'answer' => 'Your page is built for sharing and discovery, with a clean URL and structured content about your trade and area. Strong results still depend on reviews, consistent job logs, and clients sharing your link — Kraftrack gives you something worth ranking and recommending.',
                'category' => 'My Page',
                'sort_order' => 440,
                'is_featured' => false,
                'featured_sort' => null,
            ],

            // Subscriptions
            [
                'question' => 'When should I upgrade from free?',
                'answer' => 'Upgrade when five review requests a month are not enough — busy seasons, multiple crews, or rapid growth. Free still lets you log jobs and maintain your page; paid unlocks higher review volume and optional extras like a custom link.',
                'category' => 'Subscriptions',
                'sort_order' => 500,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'How do I pay for a subscription?',
                'answer' => 'Pay with methods built for Nigeria — bank transfer, USSD, or card when available. Follow the checkout flow in your account. We do not silently auto-charge a stored card without your intent to renew.',
                'category' => 'Subscriptions',
                'sort_order' => 510,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'What happens if my paid plan expires?',
                'answer' => 'Your public page and logged jobs remain part of your history. Paid allowances (such as extra review requests) pause until you renew. You are not wiped for going back to free limits — renew when you need the higher volume again.',
                'category' => 'Subscriptions',
                'sort_order' => 520,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'Are there hidden fees?',
                'answer' => 'Pricing is shown up front for review allowances and extras. There is no fee to create an account, log jobs on free, or share your page. If a feature costs money, you will see it before you pay.',
                'category' => 'Subscriptions',
                'sort_order' => 530,
                'is_featured' => false,
                'featured_sort' => null,
            ],

            // General
            [
                'question' => 'Is Kraftrack only for Nigeria?',
                'answer' => 'Kraftrack is built Nigeria-first — WhatsApp workflows, mobile networks, and local payment habits. The product language and design assume that context. If you work elsewhere, contact us; core craft remains finishing jobs and collecting honest client reviews.',
                'category' => 'General',
                'sort_order' => 600,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'How is my data protected?',
                'answer' => 'We take account security and personal data seriously. Use a strong password, keep your login private, and review our Privacy Policy for how we store and use information. Public pages only show what you choose to publish plus client-submitted reviews.',
                'category' => 'General',
                'sort_order' => 610,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'How do I contact support?',
                'answer' => 'Use the Contact page for general questions, or Help & support when you are signed in. Include your account email and a short description of the issue so we can respond faster. For careers, see the Careers page separately.',
                'category' => 'General',
                'sort_order' => 620,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'Where can I learn the full product flow?',
                'answer' => 'Read How it works for the job → review → public page loop in plain language. Then create an account and log a practice job so the flow is concrete, not theoretical.',
                'category' => 'General',
                'sort_order' => 630,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'Does Kraftrack sell leads or force me onto a marketplace?',
                'answer' => 'No. Kraftrack is not a bidding marketplace that owns your client relationship. You build a shareable page of finished work and real reviews, then clients contact you on WhatsApp. You keep the relationship.',
                'category' => 'General',
                'sort_order' => 640,
                'is_featured' => false,
                'featured_sort' => null,
            ],
        ];
    }
}
