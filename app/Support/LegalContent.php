<?php

namespace App\Support;

/**
 * Public legal documents for Inertia LegalDocument pages.
 * Content is oriented to Nigerian artisans, clients, and NDPR expectations.
 */
final class LegalContent
{
    /**
     * @return array<string, array{
     *     title: string,
     *     eyebrow: string,
     *     summary: string,
     *     updated: string,
     *     sections: list<array{heading: string, paragraphs: list<string>}>
     * }>
     */
    public static function all(): array
    {
        return [
            'terms' => self::terms(),
            'privacy' => self::privacy(),
            'cookies' => self::cookies(),
            'acceptable-use' => self::acceptableUse(),
        ];
    }

    /**
     * @return array{title: string, eyebrow: string, summary: string, updated: string, sections: list<array{heading: string, paragraphs: list<string>}>}
     */
    public static function terms(): array
    {
        return [
            'title' => 'Terms of use',
            'eyebrow' => 'Legal',
            'summary' => 'The rules for using Kraftrack — for artisans building public pages, clients leaving reviews, and visitors browsing profiles across Nigeria.',
            'updated' => '13 September 2026',
            'sections' => [
                [
                    'heading' => '1. Who we are',
                    'paragraphs' => [
                        'Kraftrack (“we”, “us”, “our”) provides a proof-of-work platform for skilled trades. Artisans can log finished jobs, request client reviews, and share a public page. Clients can leave reviews through a private link without creating an account.',
                        'These Terms govern access to kraftrack.com and related services. By creating an account, submitting a review, or using the product, you agree to these Terms.',
                    ],
                ],
                [
                    'heading' => '2. Eligibility and accounts',
                    'paragraphs' => [
                        'You must be able to form a binding contract under Nigerian law. If you register an artisan account, you confirm that the business name, trade, and contact details you provide are accurate and that you are authorised to represent that business.',
                        'You are responsible for keeping your login credentials secure and for activity under your account. Notify us promptly at hello@kraftrack.com if you suspect unauthorised access.',
                    ],
                ],
                [
                    'heading' => '3. The service',
                    'paragraphs' => [
                        'Kraftrack lets artisans log jobs (description, date, optional media and location), generate review invitations tied to a specific job, and publish a public profile at a unique URL. Reviews are written by clients; artisans cannot invent or edit client testimonials.',
                        'Features, pricing, and credit packs may change. Free tiers and paid top-ups (for example extra review requests or custom links) are described on the product. Amounts are shown in Nigerian Naira (₦) unless we state otherwise.',
                        'We aim for reliable uptime but do not guarantee uninterrupted access. Mobile networks, WhatsApp delivery, and third-party payment providers are outside our full control.',
                    ],
                ],
                [
                    'heading' => '4. Your content',
                    'paragraphs' => [
                        'You retain ownership of job descriptions, photos, and other content you upload. You grant us a licence to host, display, and process that content so the product can work — including showing it on your public page and sending review links.',
                        'You must only upload content you have rights to use. Do not post others’ private data (for example client phone numbers or exact home addresses) on public pages. Private fields we designate as non-public stay off the public profile.',
                        'Client reviews remain the reviewer’s words. We may remove or hide content that breaks these Terms, our Acceptable Use Policy, or applicable law.',
                    ],
                ],
                [
                    'heading' => '5. Reviews and honesty',
                    'paragraphs' => [
                        'Review links are meant for the real client of that job. Pressuring someone to leave a false review, reviewing your own work, impersonating a client, or fabricating vouch information is prohibited and may lead to suspension.',
                        'Clients should only submit reviews for work they commissioned. Reviews should be honest opinions, not defamation or harassment.',
                    ],
                ],
                [
                    'heading' => '6. Payments',
                    'paragraphs' => [
                        'Where you buy credits or plans, payment is processed by our payment partners. We do not store full card numbers for recurring billing on free accounts. Keep receipts from your bank or wallet for your records.',
                        'Credits and purchases are described at the point of sale. Unless Nigerian consumer law requires otherwise, unused promotional grants may expire as stated when issued; purchased credits follow the rules shown at purchase.',
                    ],
                ],
                [
                    'heading' => '7. Acceptable use',
                    'paragraphs' => [
                        'You must follow our Acceptable Use Policy. In short: no illegal services, no fake proof, no harassment, no scraping that harms the service, and no attempts to bypass security or review integrity.',
                    ],
                ],
                [
                    'heading' => '8. Suspension and termination',
                    'paragraphs' => [
                        'We may suspend or terminate accounts that violate these Terms, harm other users, or create legal risk. You may stop using Kraftrack and request account closure by contacting hello@kraftrack.com. Some records may be retained where required for security, disputes, or law.',
                    ],
                ],
                [
                    'heading' => '9. Disclaimers',
                    'paragraphs' => [
                        'Public profiles are supplied by artisans. Kraftrack does not employ the artisans listed, does not guarantee workmanship, and is not a party to jobs arranged off-platform (including via WhatsApp). Always apply your own judgment before hiring.',
                        'The service is provided on an “as available” basis. To the fullest extent permitted by Nigerian law, we exclude liability for indirect losses, lost profits, or data loss arising from use of the product, except where we cannot lawfully limit liability (for example fraud or death/personal injury caused by negligence).',
                    ],
                ],
                [
                    'heading' => '10. Governing law',
                    'paragraphs' => [
                        'These Terms are governed by the laws of the Federal Republic of Nigeria. Courts in Nigeria have jurisdiction, without prejudice to mandatory consumer protections that apply to you.',
                    ],
                ],
                [
                    'heading' => '11. Changes and contact',
                    'paragraphs' => [
                        'We may update these Terms. Material changes will be posted on this page with a new “Last updated” date. Continued use after changes means you accept the updated Terms.',
                        'Questions: hello@kraftrack.com.',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{title: string, eyebrow: string, summary: string, updated: string, sections: list<array{heading: string, paragraphs: list<string>}>}
     */
    public static function privacy(): array
    {
        return [
            'title' => 'Privacy policy',
            'eyebrow' => 'Legal',
            'summary' => 'How Kraftrack collects, uses, and protects personal data — aligned with Nigeria’s NDPR expectations and built for artisans and clients who work mostly on mobile and WhatsApp.',
            'updated' => '13 September 2026',
            'sections' => [
                [
                    'heading' => '1. Introduction',
                    'paragraphs' => [
                        'This Privacy Policy explains what personal data we process when you use Kraftrack, why we process it, and the choices you have. We design the product so public proof (jobs and reviews) is visible, while sensitive details stay private where possible.',
                        'We process personal data in line with the Nigeria Data Protection Act / NDPR framework and other applicable law. If you have questions, email hello@kraftrack.com with “Privacy” in the subject.',
                    ],
                ],
                [
                    'heading' => '2. Data we collect',
                    'paragraphs' => [
                        'Account data: name, email, password (stored hashed), business name, trade, area/city, optional profile photo, WhatsApp-related contact details you choose to provide, and SEO slug.',
                        'Work-log data: job descriptions, dates, optional media (for example via Cloudinary), optional location notes, and metadata needed to show your timeline.',
                        'Review data: star ratings, comments, optional photos, optional “who told you” vouch answers, and technical data tied to the review token (so the link maps to the right job). Clients generally do not need an account.',
                        'Billing data: purchase history and credit usage. Card or wallet details are handled by payment processors — we do not store full card PANs for recurring free-tier billing.',
                        'Support and ops data: messages you send us, moderation notes, and security logs (IP, device/browser signals) used to protect accounts and detect abuse.',
                        'Cookies and similar tech: see our Cookie Policy.',
                    ],
                ],
                [
                    'heading' => '3. How we use data',
                    'paragraphs' => [
                        'To run your account and public page; send review invitations you request; show client reviews next to jobs; process payments and credits; provide support; improve reliability and prevent fraud or fake reviews; and meet legal obligations.',
                        'We do not sell your personal data. We do not use client review text to train public AI models in a way that publishes private identities without need.',
                    ],
                ],
                [
                    'heading' => '4. What appears on public pages',
                    'paragraphs' => [
                        'Your public profile is meant to be shared — typically business name, trade, area, photo, work-log timeline, and client reviews. Keep private client phones, exact home addresses, and job prices off fields that publish publicly.',
                        'Review links are unique. Anyone with the link can submit a review for that job, so only send links to the intended client (usually via WhatsApp).',
                    ],
                ],
                [
                    'heading' => '5. Sharing',
                    'paragraphs' => [
                        'We use processors that help us operate (hosting, email delivery, media storage such as Cloudinary, analytics if you consent, and payment gateways). They may process data in or outside Nigeria under contracts that require appropriate protection.',
                        'We may disclose data if required by law, court order, or to protect users and the integrity of reviews. In a business transfer, data may move with the service under continued protection commitments.',
                    ],
                ],
                [
                    'heading' => '6. Retention',
                    'paragraphs' => [
                        'We keep account and public-page data while your account is active. After closure requests, we delete or anonymise personal data when we no longer need it, except where retention is required for disputes, security, accounting, or law.',
                        'Review content tied to a public job may remain as part of the historical record unless removal is required under these policies or law.',
                    ],
                ],
                [
                    'heading' => '7. Security',
                    'paragraphs' => [
                        'We use industry-standard measures such as encrypted transport (HTTPS), hashed passwords, and access controls for staff tools. No online service is perfectly secure — protect your email inbox and devices, especially on shared phones common in many Nigerian workplaces.',
                    ],
                ],
                [
                    'heading' => '8. Your rights',
                    'paragraphs' => [
                        'Subject to NDPR and applicable law, you may request access, correction, deletion, or restriction of your personal data, and object to certain processing. Artisans can update much of their profile in-product. For other requests, email hello@kraftrack.com.',
                        'Clients who left a review can contact us with enough detail to locate the submission if they need a correction for accuracy or legal reasons. We may need to verify identity before changing records.',
                    ],
                ],
                [
                    'heading' => '9. Children',
                    'paragraphs' => [
                        'Kraftrack is aimed at working adults and trade businesses. We do not knowingly collect data from children for accounts. If you believe a minor’s data was submitted, contact us to remove it.',
                    ],
                ],
                [
                    'heading' => '10. Changes',
                    'paragraphs' => [
                        'We may update this Policy. The “Last updated” date will change when we do. Significant changes may also be highlighted in-product or by email where appropriate.',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{title: string, eyebrow: string, summary: string, updated: string, sections: list<array{heading: string, paragraphs: list<string>}>}
     */
    public static function cookies(): array
    {
        return [
            'title' => 'Cookie policy',
            'eyebrow' => 'Legal',
            'summary' => 'How Kraftrack uses cookies and similar technologies on mobile and desktop — including what is essential for login and what needs your choice.',
            'updated' => '13 September 2026',
            'sections' => [
                [
                    'heading' => '1. What are cookies?',
                    'paragraphs' => [
                        'Cookies are small text files stored on your device. Similar technologies include local storage and session tokens used by web apps. On many Nigerian mobile connections, these help keep you signed in without re-entering details every time you open a slow or intermittent tab.',
                    ],
                ],
                [
                    'heading' => '2. Essential cookies',
                    'paragraphs' => [
                        'We use essential cookies and storage for security, authentication sessions, CSRF protection, and remembering cookie preferences you set. These are required for the site to work and cannot be switched off from our banner without breaking login or core features.',
                    ],
                ],
                [
                    'heading' => '3. Analytics and improvement (optional)',
                    'paragraphs' => [
                        'If we enable analytics cookies, we use them to understand which pages are used (for example signup, directory, or FAQ) so we can improve the product. Where required, these only run after you accept optional cookies in our consent banner.',
                        'You can change your mind anytime via “Manage cookies” in the site footer or by clearing site data in your browser.',
                    ],
                ],
                [
                    'heading' => '4. Third parties',
                    'paragraphs' => [
                        'Payment providers, media hosts, and messaging deep-links (for example opening WhatsApp) may set their own cookies or app identifiers when you leave Kraftrack. Their policies apply on those services.',
                    ],
                ],
                [
                    'heading' => '5. How long cookies last',
                    'paragraphs' => [
                        'Session cookies expire when you close the browser (or after a short idle period). Persistent cookies last longer so preferences and login can survive reopenings — subject to your device settings and our session rules.',
                    ],
                ],
                [
                    'heading' => '6. Managing cookies',
                    'paragraphs' => [
                        'Use our on-site cookie controls for optional categories. You can also block cookies in browser settings; note that blocking essentials may prevent sign-in. On shared devices (common in workshops and cyber cafés), sign out when finished and avoid “remember me” if the phone is not yours.',
                    ],
                ],
                [
                    'heading' => '7. Contact',
                    'paragraphs' => [
                        'Questions about cookies or privacy: hello@kraftrack.com. See also our Privacy Policy.',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{title: string, eyebrow: string, summary: string, updated: string, sections: list<array{heading: string, paragraphs: list<string>}>}
     */
    public static function acceptableUse(): array
    {
        return [
            'title' => 'Acceptable use',
            'eyebrow' => 'Legal',
            'summary' => 'What is allowed on Kraftrack — especially around reviews, impersonation, and promoting services lawfully in Nigeria.',
            'updated' => '13 September 2026',
            'sections' => [
                [
                    'heading' => '1. Purpose',
                    'paragraphs' => [
                        'Kraftrack exists so finished work and real client voices can travel with artisans. This Acceptable Use Policy (AUP) protects that promise. It applies to artisans, clients leaving reviews, and anyone using our sites or APIs.',
                    ],
                ],
                [
                    'heading' => '2. Allowed use',
                    'paragraphs' => [
                        'Log genuine jobs you performed. Request reviews from real clients (typically via WhatsApp). Share your public page and QR code. Browse the artisan directory and contact artisans through channels they publish. Leave honest reviews for work you commissioned.',
                    ],
                ],
                [
                    'heading' => '3. Prohibited conduct',
                    'paragraphs' => [
                        'Fake or coerced reviews: writing your own testimonials, paying for false five-star reviews, threatening clients who decline to review, or using another person’s phone to submit praise.',
                        'Impersonation: posing as a client, another artisan, or Kraftrack staff; using someone else’s business identity or media without permission.',
                        'Illegal or harmful services: promoting activities that are illegal in Nigeria, scams, fraud, or content that exploits children.',
                        'Harassment and hate: abuse, threats, doxxing, or discriminatory attacks in jobs, reviews, chat, or messages.',
                        'Platform abuse: malware, credential stuffing, scraping that overloads or bypasses access controls, circumventing suspensions, or attempting to alter review integrity in the database or UI.',
                        'Spam and misleading marketing: mass unsolicited outreach using Kraftrack data, or claiming “Kraftrack verified” in ways we do not offer.',
                    ],
                ],
                [
                    'heading' => '4. Media and job logs',
                    'paragraphs' => [
                        'Upload only photos/videos you have rights to use. Do not upload intimate images, others’ identity documents, or client interiors in a way that exposes them to risk without consent. Prefer showing finished workmanship over private living details.',
                    ],
                ],
                [
                    'heading' => '5. Enforcement',
                    'paragraphs' => [
                        'We may hide content, limit features, suspend accounts, refuse payouts/credits where fraud is involved, and cooperate with lawful requests. Serious abuse (fake review rings, impersonation, fraud) may result in permanent bans.',
                        'If you believe content or an account violates this AUP, email hello@kraftrack.com with links and context. We may not share full investigation details for safety and privacy reasons.',
                    ],
                ],
                [
                    'heading' => '6. Relationship to other policies',
                    'paragraphs' => [
                        'This AUP sits alongside our Terms of use, Privacy Policy, and Cookie Policy. If there is a conflict on safety or integrity of reviews, we may act under this AUP to protect users.',
                    ],
                ],
                [
                    'heading' => '7. Updates',
                    'paragraphs' => [
                        'We may update this AUP as the product and Nigerian regulatory landscape evolve. Continued use after the updated date means you accept the revised rules.',
                    ],
                ],
            ],
        ];
    }
}
