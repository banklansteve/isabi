<template>
    <Head :title="pageTitle" />

    <AdminChrome :title="pageTitle" eyebrow="Ops · Growth &amp; lifecycle" />

    <div class="space-y-5">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <h1 class="font-editorial text-[1.6rem] font-semibold leading-tight tracking-tight text-ink sm:text-[1.9rem]">
                    {{ pageTitle }}
                </h1>
                <p class="mt-1 max-w-2xl text-[13px] font-medium leading-relaxed text-ink/50">
                    {{ pageHint }}
                </p>
            </div>
        </header>

        <div class="no-scrollbar -mx-1 flex gap-2 overflow-x-auto px-1 pb-1">
            <button
                v-for="chip in kindChips"
                :key="chip.key"
                type="button"
                class="flex shrink-0 items-center gap-2 rounded-full px-3.5 py-2 text-[13px] font-semibold transition-all duration-150 active:scale-[0.97]"
                :class="activeKind === chip.key ? 'bg-base-action text-white shadow-sm' : 'bg-white text-ink/55 ring-1 ring-ink/[0.06] hover:text-deep'"
                :aria-current="activeKind === chip.key ? 'page' : undefined"
                @click="selectKind(chip.key)"
            >
                {{ chip.label }}
                <span
                    class="rounded-full px-1.5 py-0.5 text-[10px] font-bold tabular-nums"
                    :class="activeKind === chip.key ? 'bg-white/25 text-white' : 'bg-pale text-ink/45'"
                >
                    {{ chip.count }}
                </span>
            </button>
        </div>

        <div class="no-scrollbar -mx-1 flex gap-2 overflow-x-auto px-1 pb-1">
            <button
                v-for="chip in windowChips"
                :key="chip.key"
                type="button"
                class="flex shrink-0 items-center gap-2 rounded-full px-3.5 py-2 text-[13px] font-semibold transition-all duration-150 active:scale-[0.97]"
                :class="activeWindow === chip.key ? 'bg-ink text-white shadow-sm' : 'bg-white text-ink/55 ring-1 ring-ink/[0.06] hover:text-deep'"
                :aria-current="activeWindow === chip.key ? 'page' : undefined"
                @click="selectWindow(chip.key)"
            >
                {{ chip.label }}
                <span
                    class="rounded-full px-1.5 py-0.5 text-[10px] font-bold tabular-nums"
                    :class="activeWindow === chip.key ? 'bg-white/25 text-white' : 'bg-pale text-ink/45'"
                >
                    {{ chip.count }}
                </span>
            </button>
        </div>

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
                :title="emptyTitle"
                :description="emptyDescription"
                :icon="activeKind === 'dormant' ? 'ti ti-user-off' : 'ti ti-flame'"
            />
        </div>
    </div>

    <OpsOutreachDialog
        :open="!!outreachUser"
        :user="outreachUser"
        :category="outreachCategory"
        :title="outreachTitle"
        description="Send a templated email and/or in-app message. WhatsApp opens a pre-filled chat."
        @close="outreachUser = null"
    />
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import OpsOutreachDialog from '@/Components/Admin/OpsOutreachDialog.vue';
import OpsPersonCard from '@/Components/Admin/OpsPersonCard.vue';
import { prefetchAdmin } from '@/utils/adminVisit';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    kind: { type: String, default: 'login' },
    rows: { type: Array, default: () => [] },
    counts: { type: Object, default: () => ({}) },
    window: { type: String, default: '30' },
    windows: { type: Array, default: () => ['30', '60', '90'] },
    login_counts: { type: Object, default: () => ({}) },
    dormant_counts: { type: Object, default: () => ({}) },
});

const items = ref([...props.rows]);
const outreachUser = ref(null);
const pending = ref(false);
const activeKind = ref(props.kind);
const activeWindow = ref(props.window);

watch(() => props.rows, (value) => {
    items.value = [...value];
});

watch(() => props.kind, (value) => {
    if (!pending.value) activeKind.value = value;
});

watch(() => props.window, (value) => {
    if (!pending.value) activeWindow.value = value;
});

const isDormant = computed(() => activeKind.value === 'dormant');

const pageTitle = computed(() =>
    isDormant.value ? 'Dormant / at-risk artisans' : 'Re-engagement outreach',
);

const pageHint = computed(() =>
    isDormant.value
        ? 'Artisans with no productive jobs or reviews for a while — follow up by email, in-app, or WhatsApp.'
        : 'Don’t let churn stay silent. Nudge inactive artisans back by email, in-app, or WhatsApp.',
);

const emptyTitle = computed(() =>
    isDormant.value ? 'No dormant artisans here' : "No one's gone quiet",
);

const emptyDescription = computed(() =>
    isDormant.value
        ? 'Nobody looks at-risk in this quiet window right now.'
        : 'No artisans are inactive beyond this window right now.',
);

const outreachCategory = computed(() => (isDormant.value ? 'dormant' : 'reengagement'));
const outreachTitle = computed(() => (isDormant.value ? 'Dormant follow-up' : 'Re-engagement'));

const kindChips = computed(() => [
    {
        key: 'login',
        label: 'Login quiet',
        count: Number(props.login_counts?.[activeKind.value === 'login' ? activeWindow.value : '30'] || 0),
    },
    {
        key: 'dormant',
        label: 'Dormant / at-risk',
        count: Number(props.dormant_counts?.[activeKind.value === 'dormant' ? activeWindow.value : '30'] || 0),
    },
]);

const windowChips = computed(() => props.windows.map((key) => ({
    key,
    label: `${key}+ days quiet`,
    count: props.counts[key] ?? 0,
})));

const hrefFor = (params = {}) => route('admin.reengagement.index', {
    kind: activeKind.value,
    window: activeWindow.value,
    ...params,
});

const visit = (params = {}) => {
    if (params.kind != null) activeKind.value = params.kind;
    if (params.window != null) activeWindow.value = params.window;

    pending.value = true;

    router.get(hrefFor(params), {}, {
        only: ['kind', 'rows', 'counts', 'window', 'windows', 'login_counts', 'dormant_counts'],
        preserveScroll: true,
        preserveState: true,
        replace: true,
        showProgress: false,
        onFinish: () => {
            pending.value = false;
            activeKind.value = props.kind;
            activeWindow.value = props.window;
        },
    });
};

const selectKind = (key) => {
    if (key === activeKind.value || pending.value) return;
    visit({ kind: key });
};

const selectWindow = (key) => {
    if (key === activeWindow.value || pending.value) return;
    visit({ window: key });
};

onMounted(() => {
    const otherKind = props.kind === 'login' ? 'dormant' : 'login';
    prefetchAdmin(route('admin.reengagement.index', { kind: otherKind, window: props.window }));

    (props.windows || []).forEach((window) => {
        if (window === props.window) return;
        prefetchAdmin(route('admin.reengagement.index', { kind: props.kind, window }));
    });
});

const badgesFor = (person) => {
    const out = [];
    if (person.risk?.label) {
        out.push({
            label: person.risk.label,
            class: person.risk.level === 'critical'
                ? 'bg-rose-50 text-rose-700'
                : person.risk.level === 'high'
                    ? 'bg-orange-50 text-orange-700'
                    : 'bg-amber-50 text-amber-700',
        });
    } else if (person.never_returned) {
        out.push({ label: 'Never returned', class: 'bg-red-50 text-red-600' });
    } else if (person.days_inactive != null) {
        out.push({ label: `${person.days_inactive}d away`, class: 'bg-amber-50 text-amber-700' });
    }
    if (person.jobs > 0) {
        out.push({ label: `${person.jobs} job${person.jobs === 1 ? '' : 's'}`, class: 'bg-pale text-ink/50' });
    }
    return out;
};

const metaFor = (person) => {
    const bits = [];
    if (person.state) bits.push(person.state);
    if (person.detail) bits.push(person.detail);
    else if (person.last_seen) bits.push(`Last seen ${person.last_seen}`);
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
