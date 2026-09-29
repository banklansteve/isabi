<template>
    <Head title="Onboarding follow-up" />

    <AdminChrome title="Onboarding follow-up" eyebrow="Ops · Growth &amp; lifecycle" />

    <div class="space-y-5">
        <header class="flex flex-col gap-1">
            <h1 class="font-editorial text-[1.6rem] font-semibold leading-tight tracking-tight text-ink sm:text-[1.9rem]">
                Onboarding follow-up
            </h1>
            <p class="max-w-2xl text-[13px] font-medium leading-relaxed text-ink/50">
                Reach out to artisans stuck mid-funnel. Each tab is a drop-off point — nudge them by email, in-app, or WhatsApp before they go cold.
            </p>
        </header>

        <div class="no-scrollbar -mx-1 flex gap-2 overflow-x-auto px-1 pb-1">
            <button
                v-for="chip in chips"
                :key="chip.key"
                type="button"
                class="flex shrink-0 items-center gap-2 rounded-full px-3.5 py-2 text-[13px] font-semibold transition-all duration-150 active:scale-[0.97]"
                :class="activeSegment === chip.key ? 'bg-base-action text-white shadow-sm' : 'bg-white text-ink/55 ring-1 ring-ink/[0.06] hover:text-deep'"
                :aria-current="activeSegment === chip.key ? 'page' : undefined"
                @click="select(chip.key)"
            >
                {{ chip.label }}
                <span
                    class="rounded-full px-1.5 py-0.5 text-[10px] font-bold tabular-nums"
                    :class="activeSegment === chip.key ? 'bg-white/25 text-white' : 'bg-pale text-ink/45'"
                >
                    {{ chip.count }}
                </span>
            </button>
        </div>

        <p class="rounded-xl bg-tint/60 px-3.5 py-2.5 text-[12px] font-semibold text-deep">
            <i class="ti ti-bulb mr-1" aria-hidden="true" />{{ hint }}
        </p>

        <div
            class="transition-opacity duration-150"
            :class="pending ? 'pointer-events-none opacity-50' : 'opacity-100'"
            :aria-busy="pending ? 'true' : undefined"
        >
            <div v-if="items.length" class="space-y-2.5">
                <OpsPersonCard
                    v-for="person in items"
                    :key="person.id"
                    :person="person"
                    :badges="badgesFor(person)"
                    :meta-tail="metaFor(person)"
                >
                    <template #actions>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-base-action px-3 py-2 text-[12px] font-bold text-white shadow-[0_8px_18px_-10px_rgba(26,79,181,0.45)] hover:bg-base-hover"
                            @click="openOutreach(person)"
                        >
                            <i class="ti ti-mail-forward text-sm" aria-hidden="true" /> Reach out
                        </button>
                        <Link
                            :href="route('admin.users.show', person.id)"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-pale px-3 py-2 text-[12px] font-bold text-ink/60 hover:bg-tint hover:text-deep"
                        >
                            <i class="ti ti-user-search text-sm" aria-hidden="true" /> Open
                        </Link>
                    </template>
                </OpsPersonCard>
            </div>

            <AdminEmpty
                v-else
                class="rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]"
                title="No one stuck here"
                description="Nobody is sitting at this drop-off point right now."
                icon="ti ti-route"
            />
        </div>
    </div>

    <OpsOutreachDialog
        :open="!!outreachUser"
        :user="outreachUser"
        category="onboarding"
        title="Onboarding follow-up"
        description="Send a templated email and/or in-app message. WhatsApp opens a pre-filled chat."
        @close="outreachUser = null"
    />
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import OpsOutreachDialog from '@/Components/Admin/OpsOutreachDialog.vue';
import OpsPersonCard from '@/Components/Admin/OpsPersonCard.vue';
import { useOpsQueueVisit } from '@/Composables/useOpsQueueVisit';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, toRef, watch } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    counts: { type: Object, default: () => ({}) },
    segment: { type: String, default: 'no_job' },
});

const items = ref([...props.rows]);
const outreachUser = ref(null);

watch(() => props.rows, (value) => {
    items.value = [...value];
});

const {
    active: activeSegment,
    pending,
    visit,
} = useOpsQueueVisit({
    routeName: 'admin.onboarding.index',
    only: ['rows', 'counts', 'segment'],
    activeKey: toRef(props, 'segment'),
    paramName: 'segment',
    prefetchParam: 'segment',
    prefetchKeys: ['no_job', 'no_review', 'no_profile'],
});

const chips = computed(() => [
    { key: 'no_job', label: 'No first job', count: props.counts.no_job ?? 0 },
    { key: 'no_review', label: 'No review yet', count: props.counts.no_review ?? 0 },
    { key: 'no_profile', label: 'Thin profile', count: props.counts.no_profile ?? 0 },
]);

const hint = computed(() => ({
    no_job: 'Verified but never logged a job — help them log their first piece of work.',
    no_review: 'They log jobs but never ask clients for a review — the growth loop stalls here.',
    no_profile: 'Profile is barely started — a complete profile converts far better.',
}[activeSegment.value] || ''));

const select = (key) => {
    if (key === activeSegment.value || pending.value) return;
    visit({ segment: key });
};

const badgesFor = (person) => {
    const out = [];
    if (person.days_since_join != null) {
        out.push({ label: `${person.days_since_join}d in`, class: 'bg-pale text-ink/50' });
    }
    if (person.profile_completion < 50) {
        out.push({ label: `${person.profile_completion}% profile`, class: 'bg-amber-50 text-amber-700' });
    }
    return out;
};

const metaFor = (person) => {
    const bits = [];
    if (person.state) bits.push(person.state);
    if (person.joined) bits.push(`Joined ${person.joined}`);
    return bits.join(' · ');
};

const openOutreach = (person) => {
    outreachUser.value = {
        ...person,
        business_name: person.name,
        name: person.person || person.name,
    };
};
</script>

<style scoped>
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>
