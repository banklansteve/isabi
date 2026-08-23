<?php

return [
    'default_description' => 'Isabi gives Nigerian artisans a public page built from real finished jobs and reviews written by the clients themselves — not self-written testimonials.',

    'pages' => [
        'home' => [
            'title' => 'Proof that gets you the next job',
            'description' => 'A public page for Nigerian artisans built from finished jobs and client-written reviews. Free to start, no card required.',
        ],
        'how-it-works' => [
            'title' => 'How Isabi works',
            'description' => 'Sign up, log a finished job, send a WhatsApp review request, and watch the client’s own words land on your public page.',
        ],
        'faq' => [
            'title' => 'Frequently asked questions',
            'description' => 'Clear answers about pricing, reviews, your public page, credits, and how Isabi works — without the sales gloss.',
        ],
        'directory' => [
            'title' => 'Find artisans with real proof of work',
            'description' => 'Browse Nigerian artisans whose public pages are built from finished jobs and reviews written by their clients.',
        ],
    ],

    /*
    | FAQ schema for /faq. Keep these in lockstep with the public answers —
    | crawlers read this block, not the accordion.
    */
    'faq' => [
        [
            'question' => 'Is Isabi really free to start?',
            'answer' => 'Yes. Creating your page, logging jobs, and sharing your profile are free. You only pay if you want more than five client review requests a month, or extras like a custom link.',
        ],
        [
            'question' => 'Can I write my own reviews?',
            'answer' => 'No. Reviews are written by the client on their own phone after they tap a WhatsApp link tied to that specific job. You cannot write, edit, or approve them.',
        ],
        [
            'question' => 'What details show up on my public page?',
            'answer' => 'Your business name, trade, area, bio, photos of finished jobs, and any reviews clients have left. Private details such as amounts charged and the client’s phone number never appear.',
        ],
        [
            'question' => 'How does a client actually leave a review?',
            'answer' => 'You send them a WhatsApp link for that job. They tap it, rate the work, and write in their own words. The review then appears next to the job on your page.',
        ],
        [
            'question' => 'Do I need a bank card to sign up?',
            'answer' => 'No. Sign up with your email and a few business details. When you choose to pay later, you can use bank transfer, USSD, or card.',
        ],
    ],
];
