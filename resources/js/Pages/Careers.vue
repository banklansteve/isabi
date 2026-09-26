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
                    <div class="relative mx-auto max-w-2xl text-center">
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
                            class="mx-auto mt-4 max-w-xl text-sm font-medium leading-relaxed text-white/70 sm:text-base"
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
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-ink/40">
                            Open roles
                        </p>
                        <h2
                            class="mt-3 font-editorial text-[1.65rem] font-semibold tracking-tight text-ink sm:text-[2.1rem]"
                        >
                            {{ vacancies.length ? 'Roles we’re hiring for' : 'No open roles right now' }}
                        </h2>
                        <p class="mt-3 text-sm font-medium leading-relaxed text-ink/55">
                            <template v-if="vacancies.length">
                                Search, filter, and sort open roles. Apply with our multi-step form —
                                progress saves as you go.
                            </template>
                            <template v-else>
                                We’re not hiring in volume yet. When roles open, they’ll appear here.
                            </template>
                        </p>
                    </div>
                    <p v-if="vacancies.length" class="text-[12px] font-semibold text-ink/40">
                        {{ filteredRoles.length }}
                        {{ filteredRoles.length === 1 ? 'role' : 'roles' }}
                    </p>
                </div>

                <div
                    v-if="vacancies.length"
                    class="mt-6 space-y-3 rounded-[1.35rem] bg-white p-3 shadow-premium ring-1 ring-ink/[0.06] sm:p-4"
                >
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                        <div class="relative min-w-0 flex-1">
                            <i
                                class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/30"
                                aria-hidden="true"
                            />
                            <input
                                v-model="q"
                                type="search"
                                placeholder="Search roles, departments, locations…"
                                class="w-full rounded-2xl border border-ink/10 bg-pale py-3 pl-10 pr-4 text-sm font-medium text-ink outline-none transition focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                            />
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <FormSelect
                                id="careers-dept"
                                v-model="filterDept"
                                size="sm"
                                :options="departmentOptions"
                                placeholder="All departments"
                                wrapper-class="min-w-[9.5rem] flex-1 sm:flex-none"
                            />
                            <FormSelect
                                id="careers-type"
                                v-model="filterType"
                                size="sm"
                                :options="typeOptions"
                                placeholder="All types"
                                wrapper-class="min-w-[8.5rem] flex-1 sm:flex-none"
                            />
                            <FormSelect
                                id="careers-mode"
                                v-model="filterMode"
                                size="sm"
                                :options="modeOptions"
                                placeholder="All modes"
                                wrapper-class="min-w-[8rem] flex-1 sm:flex-none"
                            />
                            <FormSelect
                                id="careers-sort"
                                v-model="sort"
                                size="sm"
                                :options="sortOptions"
                                wrapper-class="min-w-[9.5rem] flex-1 sm:flex-none"
                            />
                        </div>
                    </div>
                </div>

                <div
                    v-if="vacancies.length && filteredRoles.length"
                    class="mt-6 grid gap-4 sm:grid-cols-2"
                >
                    <article
                        v-for="role in filteredRoles"
                        :key="role.id"
                        class="group flex flex-col rounded-[1.35rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] transition duration-200 hover:-translate-y-0.5 hover:shadow-premium-hover sm:p-6"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-bold tracking-tight text-ink">
                                {{ role.title }}
                            </h3>
                        </div>
                        <div class="mt-2.5 flex flex-wrap gap-1.5">
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
                            <span
                                v-if="role.openings > 1"
                                class="rounded-full bg-tint px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wide text-deep"
                            >
                                {{ role.openings }} openings
                            </span>
                        </div>
                        <p class="mt-2 text-xs font-semibold text-ink/40">
                            <span v-if="role.department">{{ role.department }}</span>
                            <span v-if="role.department && role.location"> · </span>
                            <span v-if="role.location">{{ role.location }}</span>
                        </p>
                        <p class="mt-3 line-clamp-3 flex-1 text-sm font-medium leading-relaxed text-ink/55">
                            {{ role.summary }}
                        </p>
                        <p
                            v-if="role.closes_at || role.published_at || role.salary"
                            class="mt-3 text-[12px] font-semibold text-ink/35"
                        >
                            <span v-if="role.published_at">Posted {{ formatDate(role.published_at) }}</span>
                            <span v-if="role.published_at && role.closes_at"> · </span>
                            <span v-if="role.closes_at">Closes {{ formatDate(role.closes_at) }}</span>
                            <span v-if="role.salary">
                                <span v-if="role.published_at || role.closes_at"> · </span>
                                {{ formatSalary(role.salary) }}
                            </span>
                        </p>
                        <div class="mt-5 flex flex-wrap gap-2">
                            <Link
                                :href="route('careers.show', { slug: role.slug, vacancy: role.public_uid })"
                                class="tap-target inline-flex flex-1 items-center justify-center rounded-2xl bg-base-action px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-base-hover sm:flex-none"
                            >
                                View role
                            </Link>
                            <Link
                                :href="route('careers.apply', { slug: role.slug, vacancy: role.public_uid })"
                                class="tap-target inline-flex flex-1 items-center justify-center rounded-2xl bg-pale px-4 py-2.5 text-sm font-bold text-ink transition-colors hover:bg-ink/[0.06] sm:flex-none"
                            >
                                Apply now
                            </Link>
                        </div>
                    </article>
                </div>

                <div
                    v-else-if="vacancies.length"
                    class="mt-8 rounded-[1.35rem] bg-white px-5 py-10 text-center shadow-premium ring-1 ring-ink/[0.06]"
                >
                    <p class="text-sm font-semibold text-ink/50">No roles match your filters.</p>
                    <button
                        type="button"
                        class="mt-3 text-sm font-bold text-base-action"
                        @click="clearFilters"
                    >
                        Clear filters
                    </button>
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
import FormSelect from '@/Components/Form/FormSelect.vue';
import PublicTopBar from '@/Components/Marketing/PublicTopBar.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    canLogin: { type: Boolean, default: true },
    canRegister: { type: Boolean, default: true },
    vacancies: { type: Array, default: () => [] },
});

const q = ref('');
const filterDept = ref('');
const filterType = ref('');
const filterMode = ref('');
const sort = ref('newest');

const departments = computed(() =>
    [...new Set(props.vacancies.map((v) => v.department).filter(Boolean))].sort(),
);

const departmentOptions = computed(() => [
    { value: '', label: 'All departments' },
    ...departments.value.map((d) => ({ value: d, label: d })),
]);

const typeOptions = [
    { value: '', label: 'All types' },
    { value: 'full-time', label: 'Full-time' },
    { value: 'part-time', label: 'Part-time' },
    { value: 'contract', label: 'Contract' },
    { value: 'internship', label: 'Internship' },
];

const modeOptions = [
    { value: '', label: 'All modes' },
    { value: 'remote', label: 'Remote' },
    { value: 'hybrid', label: 'Hybrid' },
    { value: 'onsite', label: 'Onsite' },
];

const sortOptions = [
    { value: 'newest', label: 'Newest posted' },
    { value: 'closing', label: 'Closing soon' },
    { value: 'title', label: 'Title A–Z' },
];

const filteredRoles = computed(() => {
    const needle = q.value.trim().toLowerCase();
    let rows = props.vacancies.filter((role) => {
        if (filterDept.value && role.department !== filterDept.value) return false;
        if (filterType.value && role.employment_type !== filterType.value) return false;
        if (filterMode.value && role.work_mode !== filterMode.value) return false;
        if (!needle) return true;
        const hay = [role.title, role.department, role.location, role.summary]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();
        return hay.includes(needle);
    });

    rows = [...rows];
    if (sort.value === 'title') {
        rows.sort((a, b) => String(a.title).localeCompare(String(b.title)));
    } else if (sort.value === 'closing') {
        rows.sort((a, b) => {
            if (!a.closes_at && !b.closes_at) return 0;
            if (!a.closes_at) return 1;
            if (!b.closes_at) return -1;
            return String(a.closes_at).localeCompare(String(b.closes_at));
        });
    } else {
        rows.sort((a, b) => String(b.published_at || '').localeCompare(String(a.published_at || '')));
    }

    return rows;
});

const clearFilters = () => {
    q.value = '';
    filterDept.value = '';
    filterType.value = '';
    filterMode.value = '';
    sort.value = 'newest';
};

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
