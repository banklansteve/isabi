<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        if (Faq::query()->exists()) {
            return;
        }

        $rows = [
            [
                'question' => 'Is Kraftrack really free to start?',
                'answer' => 'Yes. Creating your page, logging jobs, and sharing your profile are free — no card at signup. You only pay if you want more than five client review requests a month, or extras like a custom link.',
                'category' => 'Getting started',
                'sort_order' => 10,
                'is_featured' => true,
                'featured_sort' => 1,
            ],
            [
                'question' => 'Do I need a bank card to sign up?',
                'answer' => 'No. Sign up with your email and a few business details. When you choose to pay later, you can use bank transfer, USSD, or card. We never store your card for silent auto-renew.',
                'category' => 'Getting started',
                'sort_order' => 20,
                'is_featured' => true,
                'featured_sort' => 2,
            ],
            [
                'question' => 'How do I set up my page for the first time?',
                'answer' => 'After signup, add your trade, area, WhatsApp, and a short bio. Then log your first finished job. Share your page link or QR when you are ready for clients to see your proof trail.',
                'category' => 'Getting started',
                'sort_order' => 30,
                'is_featured' => true,
                'featured_sort' => 3,
            ],
            [
                'question' => 'Can I write my own reviews?',
                'answer' => 'No — and that is by design. Only clients can submit a review through a private link tied to a logged job. You cannot write, edit, or approve their words. That is what makes Kraftrack reviews trustworthy.',
                'category' => 'Reviews',
                'sort_order' => 40,
                'is_featured' => true,
                'featured_sort' => 4,
            ],
            [
                'question' => 'How does a client actually leave a review?',
                'answer' => 'After you log a job, request a review and send the private link (usually via WhatsApp). The client opens it in a browser, rates the job, writes a short comment, and submits — typically under a minute.',
                'category' => 'Reviews',
                'sort_order' => 50,
                'is_featured' => true,
                'featured_sort' => 5,
            ],
            [
                'question' => 'What details show up on my public page?',
                'answer' => 'Visitors see your name or business name, trade, area, photo (if you add one), WhatsApp contact, work log timeline, and client reviews tied to jobs. You choose what you log; client reviews stay as submitted.',
                'category' => 'Your public page',
                'sort_order' => 60,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => "What if I don't have many jobs yet?",
                'answer' => 'Start with the next job you finish. A short, honest timeline beats an empty page. Free accounts are built for starting from zero — you do not need a long history to join.',
                'category' => 'Getting started',
                'sort_order' => 70,
                'is_featured' => false,
                'featured_sort' => null,
            ],
            [
                'question' => 'Who can see my contact details?',
                'answer' => 'Your public page shows the WhatsApp number you choose to share so clients can reach you. Private account email and payment details stay off the page.',
                'category' => 'Your public page',
                'sort_order' => 80,
                'is_featured' => false,
                'featured_sort' => null,
            ],
        ];

        foreach ($rows as $row) {
            Faq::query()->create([
                ...$row,
                'is_published' => true,
            ]);
        }
    }
}
