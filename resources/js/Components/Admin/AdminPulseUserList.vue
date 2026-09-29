<template>
    <div class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05] sm:p-6">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-[15px] font-semibold tracking-tight text-ink">{{ title }}</h3>
                <p v-if="hint" class="mt-0.5 text-[12px] font-medium text-ink/40">{{ hint }}</p>
            </div>
            <Link
                v-if="browseHref"
                :href="browseHref"
                class="rounded-full bg-pale px-3 py-1.5 text-[12px] font-semibold text-ink/55 transition hover:bg-tint hover:text-deep"
            >
                {{ browseLabel }}
            </Link>
        </div>

        <div v-if="!people.length" class="flex h-[120px] items-center justify-center text-sm font-medium text-ink/35">
            {{ emptyLabel }}
        </div>

        <ul v-else class="divide-y divide-ink/[0.06]">
            <li
                v-for="person in people"
                :key="person.id"
                class="flex flex-col gap-3 py-3.5 first:pt-0 last:pb-0 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="truncate text-[13px] font-bold text-ink">
                            {{ person.business_name || person.name }}
                        </p>
                        <span
                            v-if="person.risk"
                            class="rounded-md px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1"
                            :class="riskClass(person.risk.level)"
                        >
                            {{ person.risk.label }}
                        </span>
                        <span
                            v-else-if="person.days_inactive != null"
                            class="rounded-md bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-800 ring-1 ring-amber-200/80"
                        >
                            {{ person.days_inactive }}d quiet
                        </span>
                    </div>
                    <p class="mt-1 text-[12px] font-medium leading-relaxed text-ink/45">
                        {{ person.risk?.detail || person.detail || '—' }}
                    </p>
                    <p class="mt-1.5 flex flex-wrap gap-x-2 gap-y-0.5 text-[11px] font-medium text-ink/35">
                        <span v-if="person.trade">{{ person.trade }}</span>
                        <span v-if="person.state">· {{ person.state }}</span>
                        <span v-if="person.last_login_label">· Login {{ person.last_login_label }}</span>
                        <span v-if="person.last_job_label">· Job {{ person.last_job_label }}</span>
                        <span v-if="person.last_review_label">· Review {{ person.last_review_label }}</span>
                        <span v-if="person.signed_up_label">· Joined {{ person.signed_up_label }}</span>
                        <span v-if="person.jobs != null">· {{ person.jobs }} job{{ person.jobs === 1 ? '' : 's' }}</span>
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap gap-1.5">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded-xl bg-base-action px-2.5 py-1.5 text-[11px] font-bold text-white shadow-[0_8px_18px_-10px_rgba(26,79,181,0.45)] transition hover:bg-base-hover"
                        @click="openOutreach(person)"
                    >
                        <i class="ti ti-mail-forward text-sm" aria-hidden="true" /> Reach out
                    </button>
                    <Link
                        :href="person.activity_url || person.profile_url"
                        class="inline-flex items-center gap-1 rounded-xl bg-pale px-2.5 py-1.5 text-[11px] font-bold text-ink/60 transition hover:bg-tint hover:text-deep"
                    >
                        <i class="ti ti-history text-sm" aria-hidden="true" /> Audit
                    </Link>
                </div>
            </li>
        </ul>
    </div>

    <OpsOutreachDialog
        :open="!!outreachUser"
        :user="outreachUser"
        :categories="categories"
        :title="outreachTitle"
        description="Send a templated email and/or in-app message. WhatsApp opens a pre-filled chat."
        @close="outreachUser = null"
    />
</template>

<script setup>
import OpsOutreachDialog from '@/Components/Admin/OpsOutreachDialog.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    hint: { type: String, default: '' },
    people: { type: Array, default: () => [] },
    emptyLabel: { type: String, default: 'Nobody in this list right now' },
    browseHref: { type: String, default: '' },
    browseLabel: { type: String, default: 'Open full queue' },
    categories: { type: Array, default: () => ['dormant', 'reengagement', 'onboarding'] },
    outreachTitle: { type: String, default: 'Lifecycle outreach' },
});

const outreachUser = ref(null);

const riskClass = (level) => {
    if (level === 'critical') return 'bg-rose-50 text-rose-700 ring-rose-200/80';
    if (level === 'high') return 'bg-orange-50 text-orange-700 ring-orange-200/80';
    return 'bg-amber-50 text-amber-800 ring-amber-200/80';
};

const openOutreach = (person) => {
    outreachUser.value = {
        id: person.id,
        name: person.name,
        person: person.name,
        business_name: person.business_name || person.name,
        email: person.email,
        whatsapp: person.whatsapp,
    };
};
</script>
