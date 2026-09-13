/**
 * Shared Help / FAQ content for SupportFab, Help hub, and public FAQ.
 * popularGuest / popularAuth control which items surface in the help bubble.
 */

/** @typedef {{ label: string, route?: string, hash?: string, href?: string, authOnly?: boolean, guestOnly?: boolean }} HelpLink */
/** @typedef {{ id: string, question: string, answer: string, keywords?: string, popularGuest?: boolean, popularAuth?: boolean, links?: HelpLink[] }} HelpItem */
/** @typedef {{ id: string, title: string, icon: string, items: HelpItem[] }} HelpGroup */

/** @type {HelpGroup[]} */
export const helpGroups = [
    {
        id: 'getting-started',
        title: 'Getting started',
        icon: 'ti ti-rocket',
        items: [
            {
                id: 'free-to-start',
                question: 'Is Kraftrack really free to start?',
                answer:
                    'Yes. Creating your page, logging jobs, and sharing your profile are free — no card at signup. You only pay if you want more than five client review requests a month, or extras like a custom link.',
                keywords: 'free start signup pricing cost',
                popularGuest: true,
                popularAuth: true,
                links: [
                    { label: 'Create your free page', route: 'register', guestOnly: true },
                    { label: 'How it works', route: 'how-it-works' },
                    { label: 'Tokens & plan', route: 'tokens.index', authOnly: true },
                ],
            },
            {
                id: 'no-card',
                question: 'Do I need a bank card to sign up?',
                answer:
                    'No. Sign up with your email and a few business details. When you choose to pay later, you can use bank transfer, USSD, or card. We never store your card for silent auto-renew.',
                keywords: 'card debit bank signup payment',
                popularGuest: true,
                links: [{ label: 'Create your free page', route: 'register', guestOnly: true }],
            },
            {
                id: 'first-setup',
                question: 'How do I set up my page for the first time?',
                answer:
                    'After signup, add your trade, area, WhatsApp, and a short bio. Then log your first finished job. Share your page link or QR when you are ready for clients to see your proof trail.',
                keywords: 'setup onboarding profile first page',
                popularGuest: true,
                links: [
                    { label: 'How it works', route: 'how-it-works' },
                    { label: 'Edit account', route: 'profile.edit', authOnly: true },
                    { label: 'Open my page', route: 'page.index', authOnly: true },
                ],
            },
            {
                id: 'public-page-details',
                question: 'What details show up on my public page?',
                answer:
                    'Visitors see your name or business name, trade, area, photo (if you add one), WhatsApp contact, work log timeline, and client reviews tied to jobs. You choose what you log; client reviews stay as submitted.',
                keywords: 'public page profile details visible',
                popularGuest: true,
                popularAuth: true,
                links: [
                    { label: 'See how it works', route: 'how-it-works' },
                    { label: 'Preview my page', route: 'page.index', authOnly: true },
                ],
            },
            {
                id: 'few-jobs',
                question: "What if I don't have many jobs yet?",
                answer:
                    'Start with the next job you finish. A short, honest timeline beats an empty page. Free accounts are built for starting from zero — you do not need a long history to join.',
                keywords: 'few jobs new start empty',
            },
        ],
    },
    {
        id: 'reviews',
        title: 'Reviews & the WhatsApp flow',
        icon: 'ti ti-star',
        items: [
            {
                id: 'self-reviews',
                question: 'Can I write my own reviews?',
                answer:
                    'No — and that is by design. Only clients can submit a review through a private link tied to a logged job. You cannot write, edit, or approve their words. That is what makes Kraftrack reviews trustworthy.',
                keywords: 'self write fake own review edit',
                popularAuth: true,
                popularGuest: true,
            },
            {
                id: 'client-leave-review',
                question: 'How does a client actually leave a review?',
                answer:
                    'After you log a job, request a review and send the private link (usually via WhatsApp). The client opens it in a browser, rates the job, writes a short comment, and submits — typically under a minute.',
                keywords: 'client leave review request whatsapp link',
                links: [
                    { label: 'Open work log', route: 'work-log.index', authOnly: true },
                ],
            },
            {
                id: 'client-no-response',
                question: 'What happens if a client never responds to my review request?',
                answer:
                    'Nothing is forced. The job stays on your timeline without a review. You can send a polite reminder later. Unused free monthly review slots do not get “used up” forever for that job — the request is already counted when you first create the link.',
                keywords: 'never responds no reply reminder review',
                popularAuth: true,
                links: [
                    { label: 'Work log', route: 'work-log.index', authOnly: true },
                ],
            },
            {
                id: 'edit-delete-review',
                question: "Can I edit or delete a review I don't like?",
                answer:
                    'No — not just because you dislike it. Fair client feedback stays with the job. If a review is abusive, spam, or clearly about the wrong person, contact support and we will check it. We will not remove a genuine rating to protect feelings.',
                keywords: 'edit delete remove bad review flag',
                links: [
                    { label: 'Chat with support', route: 'help.chat', authOnly: true },
                    { label: 'Contact us', route: 'contact', guestOnly: true },
                ],
            },
            {
                id: 'no-whatsapp',
                question: "What if my client doesn't use WhatsApp?",
                answer:
                    'The review link works in any browser. WhatsApp is just the easiest way to send it. Clients do not need WhatsApp, an app download, or a Kraftrack account to leave a review.',
                keywords: 'whatsapp sms email browser client',
            },
            {
                id: 'client-account',
                question: 'Do my clients need to create an account?',
                answer:
                    'No. They open the review link, leave a rating and comment, and they are done. No signup, no app.',
                keywords: 'client account signup app',
            },
        ],
    },
    {
        id: 'work-log',
        title: 'Work log',
        icon: 'ti ti-clipboard-list',
        items: [
            {
                id: 'log-how-far-back',
                question: 'How far back can I log a job?',
                answer:
                    'You can log recent finished work. Prefer jobs you can stand behind with honest detail — Kraftrack is a living track record, not a backlog dump of old work to look established overnight.',
                keywords: 'how far back date history past job',
                links: [
                    { label: 'Log a job', route: 'work-log.create', authOnly: true },
                ],
            },
            {
                id: 'edit-job',
                question: "Can I edit a job after I've logged it?",
                answer:
                    'Yes, within short windows: the description for about 7 days, and the job date for about 48 hours — unless you have already requested a review. Photos and optional details stay editable so you can polish proof without rewriting history.',
                keywords: 'edit job change description date',
                links: [
                    { label: 'Open work log', route: 'work-log.index', authOnly: true },
                ],
            },
            {
                id: 'photo-required',
                question: 'Do I need to add a photo to every job?',
                answer:
                    'No. Photos are optional. A clear title and short note are enough. Photos help clients trust the work, so add them when you can.',
                keywords: 'photo image media required optional',
            },
            {
                id: 'delete-job',
                question: 'Can I delete a job entry?',
                answer:
                    'Jobs are meant to stay as part of your proof trail, so full deletion is not available in the app. Fix typos within the edit window instead. If something was logged in error and needs removal, contact support.',
                keywords: 'delete remove job entry',
                links: [
                    { label: 'Chat with support', route: 'help.chat', authOnly: true },
                ],
            },
        ],
    },
    {
        id: 'credits',
        title: 'Credits & billing',
        icon: 'ti ti-coin',
        items: [
            {
                id: 'credits-how',
                question: 'How do credits work, and what can I spend them on?',
                answer:
                    'Free plans include five review requests each calendar month. Extra requests use credits (shown as tokens in your wallet) — usually one per new review link. Credits also cover extras like a high-res QR download or a custom page link. Annual unlock removes those limits for the year.',
                keywords: 'credits tokens spend top-up wallet review qr',
                popularAuth: true,
                popularGuest: true,
                links: [
                    { label: 'Tokens & plan', route: 'tokens.index', authOnly: true },
                    { label: 'View pricing', route: 'home', hash: 'pricing', guestOnly: true },
                ],
            },
            {
                id: 'credits-rollover',
                question: 'Do unused credits or free monthly reviews roll over?',
                answer:
                    'Purchased credits never expire. Free monthly review requests reset each calendar month and do not roll over. Use them in the month you get them.',
                keywords: 'rollover expire unused monthly free',
            },
            {
                id: 'annual-expire',
                question: "What happens if I don't renew my annual plan?",
                answer:
                    'Annual access is a one-time payment — no silent auto-renew. We remind you before it ends. If you do not renew, your page and history stay visible; new gated actions return to free-tier limits and credits.',
                keywords: 'annual renew expire plan billing',
                links: [
                    { label: 'Tokens & plan', route: 'tokens.index', authOnly: true },
                ],
            },
            {
                id: 'payment-methods',
                question: 'What payment methods do you accept?',
                answer:
                    'When payments are enabled, you can pay by bank transfer, USSD, or debit card through trusted Nigerian processors. Choose what you are comfortable with — no standing card on file.',
                keywords: 'payment methods bank ussd card paystack',
            },
            {
                id: 'card-stored',
                question: 'Is my card information stored anywhere?',
                answer:
                    'We do not keep your card on file for recurring billing. Card payments are handled by the payment provider for that purchase only. Kraftrack never stores card numbers for silent renewals.',
                keywords: 'card stored security privacy pci',
                links: [{ label: 'Privacy policy', route: 'privacy' }],
            },
            {
                id: 'qr-cost',
                question: 'How much does the QR card cost?',
                answer:
                    'Showing and sharing your QR on your page is free. A high-resolution download for print uses credits (or is included with Annual). Physical cards or stickers are optional extras.',
                keywords: 'qr card print cost download',
            },
        ],
    },
    {
        id: 'referrals',
        title: 'Referrals',
        icon: 'ti ti-gift',
        items: [
            {
                id: 'referral-how',
                question: 'How does the referral program work?',
                answer:
                    'Share your personal invite link. When someone signs up with it and logs their first job, your referral qualifies and you earn credit rewards. You can track invites from the Referrals page.',
                keywords: 'referral invite program how',
                links: [
                    { label: 'Open referrals', route: 'referrals.index', authOnly: true },
                ],
            },
            {
                id: 'referral-reward',
                question: 'How many credits do I get per referral?',
                answer:
                    'You earn 5 credits (tokens) when a referred artisan logs their first job. Rewards do not expire.',
                keywords: 'referral credits tokens reward amount five 5',
                links: [
                    { label: 'Open referrals', route: 'referrals.index', authOnly: true },
                ],
            },
        ],
    },
    {
        id: 'account',
        title: 'Account & profile',
        icon: 'ti ti-user',
        items: [
            {
                id: 'change-whatsapp-trade',
                question: 'How do I change my WhatsApp number or trade?',
                answer:
                    'Open Account, update WhatsApp, trade, area, or bio in the relevant section, then save. Changes show on your public page once saved.',
                keywords: 'change whatsapp trade craft phone profile',
                links: [
                    { label: 'Edit account', route: 'profile.edit', authOnly: true },
                ],
            },
            {
                id: 'custom-link',
                question: 'Can I get a custom link for my page?',
                answer:
                    'Yes. A vanity slug (for example kraftrack.com/your-name) is available with credits or included in the Annual plan. Manage it from Tokens & plan when you are ready.',
                keywords: 'custom link slug vanity url',
                links: [
                    { label: 'Tokens & plan', route: 'tokens.index', authOnly: true },
                ],
            },
            {
                id: 'page-public',
                question: 'Is my page public — can anyone see it?',
                answer:
                    'Yes. Your page is built to be shared with a link or QR so new customers can check your work before they contact you. You control what you log; you cannot hide reviews once a client submits them.',
                keywords: 'public private anyone see profile',
                links: [
                    { label: 'Preview my page', route: 'page.index', authOnly: true },
                ],
            },
            {
                id: 'reset-password',
                question: 'How do I reset my password?',
                answer:
                    'If you are signed in, change it under Account → Password. If you are locked out, use Forgot password on the sign-in screen and follow the email link.',
                keywords: 'password reset forgot change',
                links: [
                    { label: 'Edit account', route: 'profile.edit', authOnly: true },
                    { label: 'Forgot password', route: 'password.request', guestOnly: true },
                ],
            },
        ],
    },
];

/** Flat list of all items */
export function allHelpItems() {
    return helpGroups.flatMap((group) =>
        group.items.map((item) => ({
            ...item,
            groupId: group.id,
            groupTitle: group.title,
        })),
    );
}

/** Preferred order for help-bubble popular topics */
const popularOrder = {
    auth: [
        'free-to-start',
        'self-reviews',
        'client-no-response',
        'public-page-details',
        'credits-how',
    ],
    guest: [
        'free-to-start',
        'no-card',
        'first-setup',
        'public-page-details',
        'self-reviews',
    ],
};

/**
 * Popular topics for the help bubble.
 * @param {'guest' | 'auth'} context
 * @param {number} [limit]
 */
export function popularTopics(context, limit = 5) {
    const items = allHelpItems();
    const byId = Object.fromEntries(items.map((item) => [item.id, item]));
    const ordered = (popularOrder[context] ?? [])
        .map((id) => byId[id])
        .filter(Boolean);

    if (ordered.length >= limit) {
        return ordered.slice(0, limit);
    }

    const key = context === 'auth' ? 'popularAuth' : 'popularGuest';
    const ids = new Set(ordered.map((i) => i.id));
    const fill = items.filter((i) => !ids.has(i.id) && i[key]);
    return [...ordered, ...fill].slice(0, limit);
}

/**
 * Search across all topics.
 * @param {string} query
 */
export function searchHelpTopics(query) {
    const q = query.trim().toLowerCase();
    if (!q) return [];
    return allHelpItems().filter(
        (item) =>
            item.question.toLowerCase().includes(q) ||
            item.answer.toLowerCase().includes(q) ||
            (item.keywords ?? '').toLowerCase().includes(q) ||
            item.groupTitle.toLowerCase().includes(q),
    );
}

/**
 * Resolve topic href for a given context.
 * @param {HelpItem} item
 * @param {boolean} isAuthenticated
 */
export function topicHref(item, isAuthenticated) {
    const base = isAuthenticated ? route('help.index') : route('faq');
    return `${base}#${item.id}`;
}
