<template>
    <Head :title="`${vacancy.title} · Careers`" />

    <div class="min-h-dvh bg-pale text-ink">
        <PublicTopBar :can-login="canLogin" :can-register="canRegister" />

        <main class="pb-16 sm:pb-24">
            <div class="mx-auto max-w-3xl px-4 pt-6 sm:px-8 sm:pt-10">
                <Link
                    :href="route('careers')"
                    class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-ink/45 transition-colors hover:text-ink"
                >
                    <i class="ti ti-arrow-left text-sm" aria-hidden="true" />
                    All roles
                </Link>

                <header class="mt-6">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-ink/40">
                        Open role
                    </p>
                    <h1
                        class="mt-3 font-editorial text-[2rem] font-semibold leading-[1.1] tracking-tight text-ink sm:text-[2.5rem]"
                    >
                        {{ vacancy.title }}
                    </h1>
                    <p class="mt-3 flex flex-wrap gap-x-2 gap-y-1 text-sm font-semibold text-ink/45">
                        <span v-if="vacancy.department">{{ vacancy.department }}</span>
                        <span v-if="vacancy.employment_type">· {{ employmentLabel(vacancy.employment_type) }}</span>
                        <span v-if="vacancy.work_mode">· {{ workModeLabel(vacancy.work_mode) }}</span>
                        <span v-if="vacancy.location">· {{ vacancy.location }}</span>
                    </p>
                    <p v-if="vacancy.closes_at" class="mt-2 text-sm font-medium text-ink/40">
                        Applications close {{ vacancy.closes_at }}
                    </p>
                    <p
                        v-if="vacancy.salary"
                        class="mt-2 text-sm font-semibold text-ink/55"
                    >
                        Salary:
                        {{ formatSalary(vacancy.salary) }}
                    </p>
                </header>

                <div class="mt-8 space-y-8">
                    <section>
                        <h2 class="text-sm font-bold uppercase tracking-[0.12em] text-ink/40">
                            About the role
                        </h2>
                        <p class="mt-3 whitespace-pre-line text-sm font-medium leading-relaxed text-ink/60 sm:text-[0.95rem]">
                            {{ vacancy.description || vacancy.summary }}
                        </p>
                    </section>

                    <section v-if="vacancy.requirements">
                        <h2 class="text-sm font-bold uppercase tracking-[0.12em] text-ink/40">
                            Requirements
                        </h2>
                        <p class="mt-3 whitespace-pre-line text-sm font-medium leading-relaxed text-ink/60 sm:text-[0.95rem]">
                            {{ vacancy.requirements }}
                        </p>
                    </section>
                </div>

                <div class="mt-10 flex flex-wrap gap-3">
                    <Link
                        :href="route('careers.apply', vacancy.public_uid)"
                        class="tap-target inline-flex items-center justify-center rounded-2xl bg-base-action px-5 py-3 text-sm font-bold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                    >
                        Apply now
                    </Link>
                    <Link
                        :href="route('careers')"
                        class="tap-target inline-flex items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-bold text-ink ring-1 ring-ink/10 transition-colors hover:bg-pale"
                    >
                        Back to careers
                    </Link>
                </div>
            </div>
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
    vacancy: { type: Object, required: true },
});

const employmentLabel = (value) =>
    ({
        'full-time': 'Full-time',
        'part-time': 'Part-time',
        contract: 'Contract',
        internship: 'Internship',
    })[value] || value;

const workModeLabel = (value) =>
    ({ remote: 'Remote', hybrid: 'Hybrid', onsite: 'Onsite' })[value] || value;

const formatSalary = (salary) => {
    const cur = salary.currency || 'NGN';
    const fmt = (n) => (n == null ? null : Number(n).toLocaleString('en-NG'));
    if (salary.min && salary.max) return `${cur} ${fmt(salary.min)} – ${fmt(salary.max)}`;
    if (salary.min) return `From ${cur} ${fmt(salary.min)}`;
    if (salary.max) return `Up to ${cur} ${fmt(salary.max)}`;
    return '';
};
</script>
