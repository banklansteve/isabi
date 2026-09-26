<template>
    <Head :title="`${vacancy.title} · Careers`" />

    <div class="min-h-dvh bg-pale text-ink">
        <PublicTopBar :can-login="canLogin" :can-register="canRegister" />

        <main class="pb-16 sm:pb-24">
            <div class="mx-auto max-w-5xl px-4 pt-6 sm:px-8 sm:pt-10">
                <Link
                    :href="route('careers')"
                    class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-ink/45 transition-colors hover:text-ink"
                >
                    <i class="ti ti-arrow-left text-sm" aria-hidden="true" />
                    All roles
                </Link>

                <section
                    class="relative mt-5 overflow-hidden rounded-[1.5rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-9 shadow-premium-ink sm:rounded-[1.75rem] sm:px-10 sm:py-12"
                >
                    <div
                        class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(45%_55%_at_100%_100%,rgba(255,106,61,0.16),transparent_50%)]"
                        aria-hidden="true"
                    />
                    <div
                        class="pointer-events-none absolute inset-0 opacity-[0.16]"
                        style="
                            background-image: radial-gradient(rgba(255, 255, 255, 0.1) 0.7px, transparent 0.7px);
                            background-size: 18px 18px;
                        "
                        aria-hidden="true"
                    />
                    <div class="relative mx-auto max-w-2xl text-center">
                        <p
                            class="inline-flex items-center justify-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/55"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-coral" aria-hidden="true" />
                            Open role
                            <span v-if="vacancy.department">· {{ vacancy.department }}</span>
                        </p>
                        <h1
                            class="mt-4 font-editorial text-[2.1rem] font-semibold leading-[1.08] tracking-tight text-white sm:text-[2.85rem]"
                        >
                            {{ vacancy.title }}
                        </h1>
                        <p class="mx-auto mt-4 text-sm font-medium leading-relaxed text-white/70 sm:text-base">
                            {{ vacancy.summary }}
                        </p>
                        <div class="mt-5 flex flex-wrap justify-center gap-2">
                            <span
                                v-if="vacancy.employment_type"
                                class="rounded-full bg-white/12 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-white/85 ring-1 ring-white/15"
                            >
                                {{ employmentLabel(vacancy.employment_type) }}
                            </span>
                            <span
                                v-if="vacancy.work_mode"
                                class="rounded-full bg-white/12 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-white/85 ring-1 ring-white/15"
                            >
                                {{ workModeLabel(vacancy.work_mode) }}
                            </span>
                            <span
                                v-if="vacancy.location"
                                class="rounded-full bg-white/12 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-white/85 ring-1 ring-white/15"
                            >
                                {{ vacancy.location }}
                            </span>
                            <span
                                v-if="vacancy.openings > 1"
                                class="rounded-full bg-coral/90 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-white"
                            >
                                {{ vacancy.openings }} openings
                            </span>
                        </div>
                        <div class="mt-7 flex flex-wrap justify-center gap-3">
                            <Link
                                :href="applyHref"
                                class="tap-target inline-flex items-center justify-center rounded-2xl bg-coral px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-coral-deep"
                            >
                                Apply now
                            </Link>
                            <Link
                                :href="route('careers')"
                                class="tap-target inline-flex items-center justify-center rounded-2xl bg-white/10 px-5 py-3 text-sm font-semibold text-white ring-1 ring-white/20 transition-colors hover:bg-white/15"
                            >
                                Browse roles
                            </Link>
                        </div>
                    </div>
                </section>
            </div>

            <div class="mx-auto mt-10 grid max-w-5xl gap-6 px-4 sm:mt-12 sm:px-8 lg:grid-cols-[1fr_18rem]">
                <div class="space-y-6">
                    <section class="rounded-[1.35rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-7">
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-base-action">
                            About the role
                        </p>
                        <h2 class="mt-2 font-editorial text-xl font-semibold tracking-tight text-ink sm:text-2xl">
                            What you’ll do
                        </h2>
                        <p class="mt-4 whitespace-pre-line text-sm font-medium leading-relaxed text-ink/60 sm:text-[0.95rem]">
                            {{ vacancy.description || vacancy.summary }}
                        </p>
                    </section>

                    <section
                        v-if="vacancy.requirements"
                        class="rounded-[1.35rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-7"
                    >
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-base-action">
                            Requirements
                        </p>
                        <h2 class="mt-2 font-editorial text-xl font-semibold tracking-tight text-ink sm:text-2xl">
                            What we’re looking for
                        </h2>
                        <p class="mt-4 whitespace-pre-line text-sm font-medium leading-relaxed text-ink/60 sm:text-[0.95rem]">
                            {{ vacancy.requirements }}
                        </p>
                    </section>

                    <section class="rounded-[1.35rem] bg-[#0B1F3A] p-5 text-white shadow-premium-ink sm:p-7">
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-white/45">
                            Ready to apply?
                        </p>
                        <h2 class="mt-2 font-editorial text-xl font-semibold tracking-tight sm:text-2xl">
                            Multi-step application — progress saves as you go
                        </h2>
                        <p class="mt-3 max-w-xl text-sm font-medium leading-relaxed text-white/65">
                            Tell us about your background, experience, and why this role fits. You’ll need a CV
                            (PDF or Word) and NDPR consent on the final step.
                        </p>
                        <Link
                            :href="applyHref"
                            class="tap-target mt-5 inline-flex items-center justify-center rounded-2xl bg-base-action px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-base-hover"
                        >
                            Start application
                        </Link>
                    </section>
                </div>

                <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                    <div class="rounded-[1.35rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06]">
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">
                            Role details
                        </p>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-[11px] font-bold uppercase tracking-wide text-ink/35">Type</dt>
                                <dd class="mt-0.5 font-semibold text-ink">
                                    {{ employmentLabel(vacancy.employment_type) || '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-bold uppercase tracking-wide text-ink/35">Work mode</dt>
                                <dd class="mt-0.5 font-semibold text-ink">
                                    {{ workModeLabel(vacancy.work_mode) || '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-bold uppercase tracking-wide text-ink/35">Location</dt>
                                <dd class="mt-0.5 font-semibold text-ink">{{ vacancy.location || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-bold uppercase tracking-wide text-ink/35">Openings</dt>
                                <dd class="mt-0.5 font-semibold text-ink">{{ vacancy.openings || 1 }}</dd>
                            </div>
                            <div v-if="vacancy.published_at">
                                <dt class="text-[11px] font-bold uppercase tracking-wide text-ink/35">Posted</dt>
                                <dd class="mt-0.5 font-semibold text-ink">{{ formatDate(vacancy.published_at) }}</dd>
                            </div>
                            <div v-if="vacancy.closes_at">
                                <dt class="text-[11px] font-bold uppercase tracking-wide text-ink/35">Closes</dt>
                                <dd class="mt-0.5 font-semibold text-ink">{{ formatDate(vacancy.closes_at) }}</dd>
                            </div>
                            <div v-if="vacancy.salary">
                                <dt class="text-[11px] font-bold uppercase tracking-wide text-ink/35">Salary</dt>
                                <dd class="mt-0.5 font-semibold text-ink">{{ formatSalary(vacancy.salary) }}</dd>
                            </div>
                        </dl>
                        <Link
                            :href="applyHref"
                            class="tap-target mt-5 flex w-full items-center justify-center rounded-2xl bg-base-action px-4 py-3 text-sm font-bold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                        >
                            Apply now
                        </Link>
                    </div>
                </aside>
            </div>
        </main>

        <SiteFooter :can-register="canRegister" />
    </div>
</template>

<script setup>
import PublicTopBar from '@/Components/Marketing/PublicTopBar.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    canLogin: { type: Boolean, default: true },
    canRegister: { type: Boolean, default: true },
    vacancy: { type: Object, required: true },
});

const applyHref = computed(() =>
    route('careers.apply', {
        slug: props.vacancy.slug,
        vacancy: props.vacancy.public_uid,
    }),
);

const employmentLabel = (value) =>
    ({
        'full-time': 'Full-time',
        'part-time': 'Part-time',
        contract: 'Contract',
        internship: 'Internship',
    })[value] || value;

const workModeLabel = (value) =>
    ({ remote: 'Remote', hybrid: 'Hybrid', onsite: 'Onsite' })[value] || value;

const formatDate = (iso) => {
    if (!iso) return '';
    const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(iso));
    if (!m) return iso;
    return `${m[3]}/${m[2]}/${m[1]}`;
};

const formatSalary = (salary) => {
    const cur = salary.currency || 'NGN';
    const fmt = (n) => (n == null ? null : Number(n).toLocaleString('en-NG'));
    if (salary.min && salary.max) return `${cur} ${fmt(salary.min)} – ${fmt(salary.max)}`;
    if (salary.min) return `From ${cur} ${fmt(salary.min)}`;
    if (salary.max) return `Up to ${cur} ${fmt(salary.max)}`;
    return '';
};
</script>
