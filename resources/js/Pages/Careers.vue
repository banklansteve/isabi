<template>
    <Head title="Careers" />

    <div class="min-h-dvh bg-pale text-ink">
        <PublicTopBar :can-login="canLogin" :can-register="canRegister" />

        <main class="pb-16 sm:pb-24">
            <div class="mx-auto max-w-5xl px-4 pt-6 sm:px-8 sm:pt-10">
                <section
                    class="relative overflow-hidden rounded-[1.5rem] bg-[#0B1F3A] px-5 py-10 shadow-premium-ink sm:rounded-[1.75rem] sm:px-10 sm:py-14"
                >
                    <div
                        class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.1),transparent_55%),radial-gradient(45%_55%_at_100%_100%,rgba(255,106,61,0.14),transparent_50%)]"
                        aria-hidden="true"
                    />
                    <div class="relative max-w-2xl">
                        <p
                            class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/50"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-coral" aria-hidden="true" />
                            Company
                        </p>
                        <h1
                            class="mt-4 font-editorial text-[2.15rem] font-semibold leading-[1.08] tracking-tight text-white sm:text-[3rem]"
                        >
                            Help build trust infrastructure for skilled trades
                        </h1>
                        <p
                            class="mt-4 max-w-xl text-sm font-medium leading-relaxed text-white/70 sm:text-base"
                        >
                            We’re a small team shipping a product Nigeria’s artisans can use on a phone,
                            over WhatsApp, without pretending reviews can be bought. If that sounds like
                            work worth doing, we’d like to hear from you.
                        </p>
                    </div>
                </section>
            </div>

            <section class="mx-auto mt-14 max-w-5xl px-4 sm:mt-20 sm:px-8">
                <div class="max-w-2xl">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-ink/40">
                        How we work
                    </p>
                    <h2
                        class="mt-3 font-editorial text-[1.65rem] font-semibold tracking-tight text-ink sm:text-[2.1rem]"
                    >
                        Remote-friendly, Nigeria-first
                    </h2>
                    <p class="mt-4 text-sm font-medium leading-relaxed text-ink/55 sm:text-[0.95rem]">
                        Most of our users are on Android phones, intermittent data, and WhatsApp as their
                        operating system. We design for that reality — not for a Silicon Valley demo. We
                        care about clear writing, careful product judgment, and honesty in reviews more
                        than vanity metrics.
                    </p>
                </div>

                <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <li
                        v-for="value in values"
                        :key="value.title"
                        class="rounded-[1.35rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-6"
                    >
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-tint text-deep"
                        >
                            <i :class="value.icon" class="text-lg" aria-hidden="true" />
                        </span>
                        <h3 class="mt-4 text-sm font-bold tracking-tight text-ink">
                            {{ value.title }}
                        </h3>
                        <p class="mt-2 text-sm font-medium leading-relaxed text-ink/50">
                            {{ value.body }}
                        </p>
                    </li>
                </ul>
            </section>

            <section class="mx-auto mt-14 max-w-5xl px-4 sm:mt-20 sm:px-8">
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-ink/40">
                    Open roles
                </p>
                <h2
                    class="mt-3 font-editorial text-[1.65rem] font-semibold tracking-tight text-ink sm:text-[2.1rem]"
                >
                    {{ vacancies.length ? 'Roles we’re hiring for' : 'No open roles right now' }}
                </h2>
                <p class="mt-3 max-w-2xl text-sm font-medium leading-relaxed text-ink/55">
                    <template v-if="vacancies.length">
                        Browse open roles below, then apply with our short multi-step form. Progress is
                        saved as you go.
                    </template>
                    <template v-else>
                        We’re not hiring in volume yet. When roles open, they’ll appear here. Exceptional
                        people who care about this problem can still introduce themselves by email.
                    </template>
                </p>

                <div v-if="vacancies.length" class="mt-8 space-y-3">
                    <article
                        v-for="role in vacancies"
                        :key="role.id"
                        class="rounded-[1.35rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-6"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-sm font-bold tracking-tight text-ink sm:text-base">
                                {{ role.title }}
                            </h3>
                            <span
                                v-if="role.employment_type"
                                class="rounded-full bg-pale px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wide text-ink/45"
                            >
                                {{ employmentLabel(role.employment_type) }}
                            </span>
                            <span
                                v-if="role.work_mode"
                                class="rounded-full bg-pale px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wide text-ink/45"
                            >
                                {{ workModeLabel(role.work_mode) }}
                            </span>
                        </div>
                        <p
                            v-if="role.department || role.location"
                            class="mt-1.5 text-xs font-semibold text-ink/40"
                        >
                            <span v-if="role.department">{{ role.department }}</span>
                            <span v-if="role.department && role.location"> · </span>
                            <span v-if="role.location">{{ role.location }}</span>
                            <span v-if="role.closes_at"> · Closes {{ role.closes_at }}</span>
                        </p>
                        <p class="mt-3 text-sm font-medium leading-relaxed text-ink/55">
                            {{ role.summary }}
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Link
                                :href="route('careers.show', role.public_uid)"
                                class="tap-target inline-flex items-center justify-center rounded-2xl bg-base-action px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-base-hover"
                            >
                                View role
                            </Link>
                            <Link
                                :href="route('careers.apply', role.public_uid)"
                                class="tap-target inline-flex items-center justify-center rounded-2xl bg-pale px-4 py-2.5 text-sm font-bold text-ink transition-colors hover:bg-ink/[0.06]"
                            >
                                Apply now
                            </Link>
                        </div>
                    </article>
                </div>
            </section>

            <section class="mx-auto mt-14 max-w-5xl px-4 sm:mt-20 sm:px-8">
                <div
                    class="overflow-hidden rounded-[1.5rem] bg-[#0B1F3A] px-5 py-8 text-white shadow-premium-ink sm:rounded-[1.75rem] sm:px-10 sm:py-12"
                >
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-white/45">
                        Introduce yourself
                    </p>
                    <h2
                        class="mt-3 max-w-lg font-editorial text-[1.55rem] font-semibold tracking-tight text-white sm:text-[1.9rem]"
                    >
                        Send a short note — not a novel
                    </h2>
                    <p class="mt-3 max-w-xl text-sm font-medium leading-relaxed text-white/70">
                        Email
                        <a
                            href="mailto:hello@kraftrack.com?subject=Careers"
                            class="font-bold text-white underline decoration-white/30 underline-offset-2 hover:decoration-white"
                        >
                            hello@kraftrack.com
                        </a>
                        with “Careers” in the subject. Include what you build (or have built), a link to
                        work if you have one, and why Kraftrack’s honesty-first model matters to you.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a
                            href="mailto:hello@kraftrack.com?subject=Careers"
                            class="tap-target inline-flex items-center justify-center rounded-2xl bg-coral px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-coral-deep"
                        >
                            Email careers
                        </a>
                        <Link
                            :href="route('contact')"
                            class="tap-target inline-flex items-center justify-center rounded-2xl bg-white/10 px-5 py-3 text-sm font-semibold text-white ring-1 ring-white/20 transition-colors hover:bg-white/15"
                        >
                            Contact form
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <SiteFooter :can-register="canRegister" />
    </div>
</template>

<script setup>
import PublicTopBar from '@/Components/Marketing/PublicTopBar.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: { type: Boolean, default: true },
    canRegister: { type: Boolean, default: true },
    vacancies: { type: Array, default: () => [] },
});

const values = [
    {
        icon: 'ti ti-device-mobile',
        title: 'Mobile before desktop',
        body: 'If it doesn’t work on a mid-range Android phone in traffic, it isn’t done.',
    },
    {
        icon: 'ti ti-shield-check',
        title: 'Integrity over growth hacks',
        body: 'We won’t ship features that let artisans ghost-write their own testimonials.',
    },
    {
        icon: 'ti ti-users',
        title: 'Respect for the trade',
        body: 'Electricians, plumbers, tailors, and builders are the customer — not a demographic joke.',
    },
];

const employmentLabel = (value) =>
    ({
        'full-time': 'Full-time',
        'part-time': 'Part-time',
        contract: 'Contract',
        internship: 'Internship',
        other: 'Other',
    })[value] || value;

const workModeLabel = (value) =>
    ({
        remote: 'Remote',
        hybrid: 'Hybrid',
        onsite: 'Onsite',
    })[value] || value;
</script>
