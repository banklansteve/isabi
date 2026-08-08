<template>
    <Head title="Pricing model — Internal" />

    <div class="min-h-dvh bg-pale text-ink">
        <header class="sticky top-0 z-40 border-b border-ink/10 bg-white/95 shadow-nav backdrop-blur-md">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-5 py-3 sm:px-8">
                <div>
                    <p class="text-[0.65rem] font-bold uppercase tracking-[0.16em] text-coral-deep">
                        Internal · Draft
                    </p>
                    <h1 class="font-display text-lg font-bold tracking-tight text-ink sm:text-xl">
                        Isabi pricing model
                    </h1>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('dashboard')"
                        class="tap-target inline-flex items-center rounded-xl px-3 text-sm font-semibold text-ink/60 transition-colors hover:text-ink"
                    >
                        Dashboard
                    </Link>
                    <a
                        href="#toc"
                        class="tap-target inline-flex items-center rounded-xl bg-ink px-3 text-sm font-semibold text-white"
                    >
                        Contents
                    </a>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-5 py-10 sm:px-8 sm:py-14">
            <p class="max-w-2xl font-voice text-2xl leading-snug text-ink sm:text-3xl">
                Built for how Nigerians actually pay: deliberate one-time charges, airtime-like
                top-ups, and a free tier that earns trust before asking for money.
            </p>
            <p class="mt-4 text-sm font-medium text-ink/45">
                Last rendered {{ updatedAt }} · Numbers from
                <code class="rounded bg-white px-1.5 py-0.5 text-ink/70 shadow-card">config/pricing.php</code>
            </p>

            <!-- TOC -->
            <nav
                id="toc"
                class="mt-10 rounded-[1.5rem] bg-white p-6 shadow-card sm:p-8"
                aria-label="Table of contents"
            >
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-ink/40">Contents</p>
                <ol class="mt-4 grid gap-2 text-sm font-semibold text-ink/75 sm:grid-cols-2">
                    <li v-for="item in toc" :key="item.href">
                        <a
                            :href="item.href"
                            class="tap-target inline-flex items-center transition-colors hover:text-base"
                        >
                            {{ item.label }}
                        </a>
                    </li>
                </ol>
            </nav>

            <!-- 1. Overview -->
            <section id="overview" class="mt-16 scroll-mt-24">
                <SectionLabel n="01" title="Overview" />
                <p class="mt-4 max-w-3xl text-base font-medium leading-relaxed text-ink/65">
                    Isabi’s pricing is built around three principles specific to the Nigerian market.
                </p>

                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <article
                        v-for="principle in principles"
                        :key="principle.title"
                        class="rounded-[1.35rem] bg-white p-5 shadow-card sm:p-6"
                    >
                        <h3 class="font-display text-lg font-bold tracking-tight text-ink">
                            {{ principle.title }}
                        </h3>
                        <p class="mt-2 text-sm font-medium leading-relaxed text-ink/60">
                            {{ principle.body }}
                        </p>
                    </article>
                </div>

                <div class="mt-8 rounded-[1.35rem] bg-ink px-6 py-6 text-white sm:px-8">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-coral">Active layers</p>
                    <p class="mt-3 font-display text-xl font-bold tracking-tight sm:text-2xl">
                        Free → Credits → Annual unlock
                    </p>
                    <p class="mt-2 text-sm font-medium leading-relaxed text-white/65">
                        Referral rewards sit alongside this stack (not inside the public pricing
                        frame). Reach &amp; visibility is planned as a later paid layer on the same rails.
                    </p>
                </div>
            </section>

            <!-- 2. Tiers -->
            <section id="tiers" class="mt-20 scroll-mt-24">
                <SectionLabel n="02" title="Tier structure" />

                <article id="free" class="mt-8 scroll-mt-24 rounded-[1.5rem] bg-white p-6 shadow-card sm:p-8">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <h3 class="font-display text-2xl font-bold tracking-tight text-ink">
                            2.1 Free tier
                        </h3>
                        <span class="rounded-full bg-tint px-3 py-1 text-xs font-bold uppercase tracking-wide text-deep">
                            Default forever
                        </span>
                    </div>
                    <p class="mt-3 text-sm font-medium leading-relaxed text-ink/60">
                        Every user starts here, indefinitely. No trial countdown, no forced upgrade
                        prompts.
                    </p>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <div class="rounded-2xl bg-pale p-5">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-base">
                                Unlimited (never gate)
                            </p>
                            <ul class="mt-3 space-y-2 text-sm font-medium text-ink/75">
                                <li>Page creation and setup</li>
                                <li>Work log entries — core trust feature</li>
                                <li>Profile viewing &amp; sharing (link + QR view)</li>
                            </ul>
                        </div>
                        <div class="rounded-2xl bg-pale p-5">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-coral-deep">
                                Capped
                            </p>
                            <ul class="mt-3 space-y-2 text-sm font-medium text-ink/75">
                                <li>
                                    {{ pricing.free.monthly_review_links }} review-request links per
                                    calendar month
                                </li>
                                <li>
                                    Unused links
                                    {{ pricing.free.review_link_rollover ? 'roll over' : 'do not roll over' }}
                                </li>
                                <li>Resets keep the model easy to explain</li>
                            </ul>
                        </div>
                    </div>
                </article>

                <article id="credits" class="mt-5 scroll-mt-24 rounded-[1.5rem] bg-white p-6 shadow-card sm:p-8">
                    <h3 class="font-display text-2xl font-bold tracking-tight text-ink">
                        2.2 Credit top-ups ({{ pricing.credits.name }})
                    </h3>
                    <p class="mt-3 text-sm font-medium leading-relaxed text-ink/60">
                        For users who prefer to pay small amounts as needed — the airtime / data-bundle
                        mental model — instead of committing to an annual fee.
                    </p>
                    <ul class="mt-5 space-y-2 text-sm font-medium text-ink/75">
                        <li>Spent across multiple actions (see Credit system), not one feature</li>
                        <li>
                            {{ pricing.credits.expire ? 'Expire on a schedule' : 'Never expire — no time pressure' }}
                        </li>
                        <li>
                            One-time purchase via bank transfer, USSD, or card — never stored for
                            recurring use
                        </li>
                    </ul>
                </article>

                <article id="annual" class="mt-5 scroll-mt-24 rounded-[1.5rem] bg-white p-6 shadow-card sm:p-8">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <h3 class="font-display text-2xl font-bold tracking-tight text-ink">
                            2.3 {{ pricing.annual.label }}
                        </h3>
                        <p class="font-display text-2xl font-extrabold tracking-tight text-ink">
                            {{ formatMoney(pricing.annual.price) }}
                            <span class="text-sm font-semibold text-ink/40">/ year</span>
                        </p>
                    </div>
                    <p class="mt-3 text-sm font-medium leading-relaxed text-ink/60">
                        Unlocks unlimited review links and all credit-gated actions. Manually renewed —
                        no auto-debit.
                    </p>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl bg-pale p-5">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-deep">
                                Renewal reminders
                            </p>
                            <p class="mt-2 text-sm font-medium text-ink/70">
                                Sequence at
                                {{ pricing.annual.reminder_days.join(' / ') }} days before expiry via
                                {{ pricing.annual.reminder_channels.join(' + ') }} — so a lapse is a
                                choice, not a missed single SMS.
                            </p>
                        </div>
                        <div class="rounded-2xl bg-pale p-5">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-deep">
                                If they don’t renew
                            </p>
                            <p class="mt-2 text-sm font-medium text-ink/70">
                                New actions gate back to free-tier limits. Nothing already on the page
                                is hidden — work log, reviews, and history stay visible. Earned track
                                record is never held hostage.
                            </p>
                        </div>
                    </div>
                </article>

                <article id="referral" class="mt-5 scroll-mt-24 rounded-[1.5rem] bg-white p-6 shadow-card sm:p-8">
                    <h3 class="font-display text-2xl font-bold tracking-tight text-ink">
                        2.4 Referral credit rewards
                    </h3>
                    <p class="mt-3 text-sm font-medium leading-relaxed text-ink/60">
                        A growth mechanic, not a pricing tier. Keep it out of the public pricing page
                        framing so “pay with money” and “earn through referrals” don’t compete.
                    </p>
                    <ul class="mt-5 space-y-2 text-sm font-medium text-ink/75">
                        <li>
                            Earn
                            <strong class="text-ink">{{ pricing.referral.credits_reward }} credits</strong>
                            when a referred artisan signs up and logs a job
                        </li>
                        <li>
                            Referral credits
                            {{ pricing.referral.expire ? 'expire' : 'do not expire' }} — earned, not
                            pressured
                        </li>
                        <li>Sits naturally next to vouch-chain behavior the product already encourages</li>
                    </ul>
                </article>

                <article id="reach" class="mt-5 scroll-mt-24 rounded-[1.5rem] border border-dashed border-ink/15 bg-white/70 p-6 sm:p-8">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-display text-2xl font-bold tracking-tight text-ink">
                            2.5 Reach &amp; visibility
                        </h3>
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-amber-700">
                            Planned
                        </span>
                    </div>
                    <p class="mt-3 text-sm font-medium leading-relaxed text-ink/60">
                        Future one-off purchases for discoverability (featured placement, profile boost).
                        Use the same payment / credit infrastructure as top-ups — an extension, not a
                        second billing system.
                    </p>
                    <Callout tone="idea" class="mt-5">
                        <strong>Recommendation:</strong> keep boosts on the same Isabi Credits ledger
                        unless boost pricing must scale wildly differently. One balance is easier to
                        explain, support, and display in the app.
                    </Callout>
                </article>
            </section>

            <!-- 3. Credits -->
            <section id="credit-system" class="mt-20 scroll-mt-24">
                <SectionLabel n="03" title="Credit system details" />

                <div class="mt-8 overflow-hidden rounded-[1.5rem] bg-white shadow-card">
                    <div class="border-b border-tint px-6 py-4 sm:px-8">
                        <h3 class="font-display text-xl font-bold tracking-tight text-ink">
                            3.1 What credits can be spent on
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[28rem] text-left text-sm">
                            <thead class="bg-pale text-xs font-bold uppercase tracking-[0.12em] text-ink/45">
                                <tr>
                                    <th class="px-6 py-3 font-bold sm:px-8">Action</th>
                                    <th class="px-6 py-3 font-bold sm:px-8">Cost</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-tint font-medium text-ink/75">
                                <tr>
                                    <td class="px-6 py-3.5 sm:px-8">
                                        Review-request link (beyond free
                                        {{ pricing.free.monthly_review_links }}/mo)
                                    </td>
                                    <td class="px-6 py-3.5 sm:px-8">
                                        {{ pricing.credits.actions.review_link }} credit
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-3.5 sm:px-8">High-resolution QR download</td>
                                    <td class="px-6 py-3.5 sm:px-8">
                                        {{ pricing.credits.actions.qr_download }} credit
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-3.5 sm:px-8">
                                        Custom / vanity profile link (one-time unlock)
                                    </td>
                                    <td class="px-6 py-3.5 sm:px-8">
                                        {{ pricing.credits.actions.vanity_slug }} credits
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-3.5 sm:px-8">Reach &amp; visibility boost</td>
                                    <td class="px-6 py-3.5 text-ink/40 sm:px-8">TBD · planned</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="border-t border-tint px-6 py-4 text-sm font-medium text-ink/55 sm:px-8">
                        Keeping most actions at 1 credit keeps mental math simple — closer to “how many
                        do I have left?” than a price sheet.
                    </p>
                </div>

                <div class="mt-5 overflow-hidden rounded-[1.5rem] bg-white shadow-card">
                    <div class="border-b border-tint px-6 py-4 sm:px-8">
                        <h3 class="font-display text-xl font-bold tracking-tight text-ink">
                            3.2 Top-up packs
                        </h3>
                        <p class="mt-1 text-sm font-medium text-ink/55">
                            Modeled on Nigerian data-bundle conventions — bulk discount at higher tiers.
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[32rem] text-left text-sm">
                            <thead class="bg-pale text-xs font-bold uppercase tracking-[0.12em] text-ink/45">
                                <tr>
                                    <th class="px-6 py-3 font-bold sm:px-8">Pack</th>
                                    <th class="px-6 py-3 font-bold sm:px-8">Credits</th>
                                    <th class="px-6 py-3 font-bold sm:px-8">Price</th>
                                    <th class="px-6 py-3 font-bold sm:px-8">Per credit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-tint font-medium text-ink/75">
                                <tr
                                    v-for="pack in pricing.credits.packs"
                                    :key="pack.key"
                                >
                                    <td class="px-6 py-3.5 font-semibold text-ink sm:px-8">
                                        {{ pack.name }}
                                    </td>
                                    <td class="px-6 py-3.5 sm:px-8">{{ pack.credits }}</td>
                                    <td class="px-6 py-3.5 sm:px-8">
                                        {{ formatMoney(pack.price) }}
                                    </td>
                                    <td class="px-6 py-3.5 sm:px-8">
                                        {{ formatMoney(pack.cost_per_credit) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <article class="mt-5 rounded-[1.5rem] bg-white p-6 shadow-card sm:p-8">
                    <h3 class="font-display text-xl font-bold tracking-tight text-ink">
                        3.3 Annual vs top-ups — does the math hold?
                    </h3>
                    <p class="mt-3 text-sm font-medium leading-relaxed text-ink/65">
                        At the best top-up rate
                        ({{ formatMoney(math.best_cost_per_credit) }}/credit), break-even against
                        {{ formatMoney(pricing.annual.price) }}/year is about
                        <strong class="text-ink">{{ math.annual_breakeven_credits }} credits/year</strong>
                        — roughly
                        <strong class="text-ink">{{ math.approx_extra_actions_per_month }} extra actions/month</strong>
                        beyond the free review-link allowance.
                    </p>
                    <p class="mt-3 text-sm font-medium leading-relaxed text-ink/65">
                        Active artisans who send review requests regularly (or mix reviews + QR +
                        vanity) honestly save money on annual. Occasional users stay on free + Starter /
                        Standard — that’s intended, not a gap.
                    </p>
                    <Callout tone="idea" class="mt-5">
                        <strong>Recommendation:</strong> in-product, show a quiet comparison when a user
                        is about to buy their second or third top-up in a year: “You’ve spent about
                        ₦X on credits this year. Annual is {{ formatMoney(pricing.annual.price) }} and
                        removes the meter.” No dark patterns — just honest math at the right moment.
                    </Callout>
                </article>
            </section>

            <!-- 4. Payments -->
            <section id="payments" class="mt-20 scroll-mt-24">
                <SectionLabel n="04" title="Payment rails" />
                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <article class="rounded-[1.5rem] bg-white p-6 shadow-card">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-base">Use</p>
                        <ul class="mt-3 space-y-2 text-sm font-medium text-ink/75">
                            <li>Processors: {{ pricing.payments.processors.join(' / ') }}</li>
                            <li>Bank transfer (virtual account or reference)</li>
                            <li>USSD</li>
                            <li>Debit card — single charge only</li>
                        </ul>
                    </article>
                    <article class="rounded-[1.5rem] bg-white p-6 shadow-card">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-coral-deep">
                            Explicitly avoid
                        </p>
                        <ul class="mt-3 space-y-2 text-sm font-medium text-ink/75">
                            <li
                                v-for="item in pricing.payments.forbidden"
                                :key="item"
                            >
                                {{ labelForbidden(item) }}
                            </li>
                        </ul>
                    </article>
                </div>
                <Callout tone="idea" class="mt-5">
                    <strong>Recommendation:</strong> prefer dedicated virtual accounts (Paystack /
                    Flutterwave) for bank transfer so artisans can pay from any bank app without
                    hunting a reference code. Confirm success with an in-app + WhatsApp receipt —
                    that receipt becomes the support trail when transfers are disputed.
                </Callout>
            </section>

            <!-- 5. Admin variables -->
            <section id="admin" class="mt-20 scroll-mt-24">
                <SectionLabel n="05" title="Admin-configurable variables" />
                <p class="mt-4 max-w-3xl text-sm font-medium leading-relaxed text-ink/60">
                    All numeric values here should be adjustable from an admin settings page without a
                    code release. Today they live in
                    <code class="rounded bg-white px-1.5 py-0.5 shadow-card">config/pricing.php</code>
                    — migrate that file’s keys into the database when admin UI ships.
                </p>

                <div class="mt-8 overflow-hidden rounded-[1.5rem] bg-white shadow-card">
                    <table class="w-full min-w-[28rem] text-left text-sm">
                        <thead class="bg-pale text-xs font-bold uppercase tracking-[0.12em] text-ink/45">
                            <tr>
                                <th class="px-6 py-3 font-bold sm:px-8">Variable</th>
                                <th class="px-6 py-3 font-bold sm:px-8">Current</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-tint font-medium text-ink/75">
                            <tr
                                v-for="row in adminRows"
                                :key="row.label"
                            >
                                <td class="px-6 py-3.5 sm:px-8">{{ row.label }}</td>
                                <td class="px-6 py-3.5 font-semibold text-ink sm:px-8">
                                    {{ row.value }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Recommendations -->
            <section id="recommendations" class="mt-20 scroll-mt-24 pb-10">
                <SectionLabel n="06" title="Implementation notes &amp; suggestions" />
                <div class="mt-8 space-y-4">
                    <Callout
                        v-for="note in recommendations"
                        :key="note.title"
                        :tone="note.tone"
                    >
                        <strong>{{ note.title }}</strong>
                        — {{ note.body }}
                    </Callout>
                </div>
            </section>
        </main>
    </div>
</template>

<script setup>
import { computed, defineComponent, h } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    pricing: {
        type: Object,
        required: true,
    },
    math: {
        type: Object,
        required: true,
    },
    updatedAt: {
        type: String,
        required: true,
    },
});

const SectionLabel = defineComponent({
    props: {
        n: { type: String, required: true },
        title: { type: String, required: true },
    },
    setup(componentProps) {
        return () =>
            h('div', { class: 'flex items-baseline gap-3' }, [
                h(
                    'span',
                    { class: 'font-display text-sm font-bold tracking-wide text-base' },
                    componentProps.n,
                ),
                h(
                    'h2',
                    { class: 'font-display text-display-md text-ink' },
                    componentProps.title,
                ),
            ]);
    },
});

const Callout = defineComponent({
    inheritAttrs: false,
    props: {
        tone: { type: String, default: 'idea' },
    },
    setup(componentProps, { slots, attrs }) {
        return () =>
            h(
                'aside',
                {
                    ...attrs,
                    class: [
                        'rounded-2xl px-5 py-4 text-sm font-medium leading-relaxed',
                        componentProps.tone === 'warn'
                            ? 'bg-coral-tint text-coral-deep'
                            : 'bg-tint text-deep',
                        attrs.class,
                    ],
                },
                slots.default?.(),
            );
    },
});

const toc = [
    { href: '#overview', label: '1. Overview' },
    { href: '#tiers', label: '2. Tier structure' },
    { href: '#credit-system', label: '3. Credit system' },
    { href: '#payments', label: '4. Payment rails' },
    { href: '#admin', label: '5. Admin variables' },
    { href: '#recommendations', label: '6. Suggestions' },
];

const principles = [
    {
        title: 'No recurring auto-debit',
        body: 'Standing instructions and card-on-file are not trusted or common here. Every charge is a deliberate, one-time action.',
    },
    {
        title: 'Pay small often — or once a year',
        body: 'Top up as you go (like airtime/data), or pay annually for peace of mind. Both paths are first-class.',
    },
    {
        title: 'Free must deliver real value',
        body: 'Trust before money. A new user should build a credible page and get at least one real review without paying.',
    },
];

const formatMoney = (amount) => {
    const value = Number(amount || 0);
    return `${props.pricing.currency_symbol}${value.toLocaleString('en-NG')}`;
};

const labelForbidden = (key) =>
    ({
        card_on_file: 'Card-on-file / tokenization for recurring billing',
        standing_instruction: 'Standing instructions',
        silent_auto_renew: 'Auto-renewal without explicit user action',
    })[key] || key;

const adminRows = computed(() => [
    {
        label: 'Free monthly review-link cap',
        value: String(props.pricing.free.monthly_review_links),
    },
    {
        label: 'Free reset behavior',
        value: props.pricing.free.review_link_rollover
            ? 'Monthly with rollover'
            : 'Monthly, no rollover',
    },
    {
        label: 'Annual unlock price',
        value: formatMoney(props.pricing.annual.price),
    },
    {
        label: 'Credit packs',
        value: props.pricing.credits.packs
            .map((pack) => `${pack.name} ${pack.credits}@${formatMoney(pack.price)}`)
            .join(' · '),
    },
    {
        label: 'Action costs',
        value: `Review ${props.pricing.credits.actions.review_link} · QR ${props.pricing.credits.actions.qr_download} · Vanity ${props.pricing.credits.actions.vanity_slug}`,
    },
    {
        label: 'Referral reward',
        value: `${props.pricing.referral.credits_reward} credits · ${props.pricing.referral.expire ? 'expires' : 'no expiry'}`,
    },
    {
        label: 'Renewal reminder timing',
        value: `${props.pricing.annual.reminder_days.join(' / ')} days before expiry`,
    },
]);

const recommendations = [
    {
        tone: 'warn',
        title: 'Align the public landing page before launch',
        body: 'The marketing site currently shows a simplified ₦2,500/mo Pro frame. This internal model (free + credits + ₦25k annual) should replace that before signup volume grows, or you’ll train the wrong expectation.',
    },
    {
        tone: 'idea',
        title: 'Never gate the work log',
        body: 'Correct call. If cash pressure ever tempts gating logs, resist — the timeline is the product’s trust engine. Gate distribution (review links, vanity, boosts), not evidence.',
    },
    {
        tone: 'idea',
        title: 'Soft meter before the hard stop',
        body: `At ${Math.max(1, props.pricing.free.monthly_review_links - 2)}/${props.pricing.free.monthly_review_links} free links used, show a calm notice: remaining count + “top up or go annual.” Avoid surprise blocks mid-WhatsApp share flow.`,
    },
    {
        tone: 'idea',
        title: 'Annual expiry vs leftover credits',
        body: 'Spell this in product copy: when annual ends, unused purchased credits remain spendable. Annual removes the meter; it shouldn’t confiscate prepaid balance.',
    },
    {
        tone: 'idea',
        title: 'Vanity slug as one-time unlock',
        body: 'Good. Persist the unlock on the account forever once purchased (or while annual is active, then keep it if bought with credits). Don’t re-charge yearly for a name — that feels like a tax on identity.',
    },
    {
        tone: 'idea',
        title: 'Referral qualification',
        body: '“Sign up + log a job” is a solid anti-spam bar. If abuse appears later, tighten to “log a job and receive one client review” before paying the 5 credits — but don’t start that strict.',
    },
    {
        tone: 'idea',
        title: 'Public naming',
        body: 'Internally “Annual unlock” is precise. Publicly, consider “Isabi Pro — yearly” so artisans have a simple label. Keep “credits” as the unit name everywhere in-app.',
    },
    {
        tone: 'idea',
        title: 'Calendar month vs rolling 30 days',
        body: 'Calendar month is easier for support (“resets on the 1st”). Rolling 30 days feels fairer but harder to explain. Stick with calendar unless data shows gaming on month-end spikes.',
    },
];
</script>
