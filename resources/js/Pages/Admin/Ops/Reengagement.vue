<template>
    <Head title="Re-engagement" />

    <AdminChrome title="Re-engagement" eyebrow="Growth &amp; lifecycle" />

    <div class="space-y-5">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <h1 class="font-editorial text-[1.6rem] font-semibold leading-tight tracking-tight text-ink sm:text-[1.9rem]">
                    Re-engagement outreach
                </h1>
                <p class="mt-1 max-w-2xl text-[13px] font-medium leading-relaxed text-ink/50">
                    Don’t let churn stay silent. Nudge inactive artisans back one-to-one on WhatsApp.
                </p>
            </div>
        </header>

        <div class="no-scrollbar -mx-1 flex gap-2 overflow-x-auto px-1 pb-1">
            <button
                v-for="chip in chips"
                :key="chip.key"
                type="button"
                class="flex shrink-0 items-center gap-2 rounded-full px-3.5 py-2 text-[13px] font-semibold transition-all duration-150 active:scale-[0.97]"
                :class="window === chip.key ? 'bg-base-action text-white shadow-sm' : 'bg-white text-ink/55 ring-1 ring-ink/[0.06] hover:text-deep'"
                @click="select(chip.key)"
            >
                {{ chip.label }}
                <span
                    class="rounded-full px-1.5 py-0.5 text-[10px] font-bold tabular-nums"
                    :class="window === chip.key ? 'bg-white/25 text-white' : 'bg-pale text-ink/45'"
                >
                    {{ chip.count }}
                </span>
            </button>
        </div>

        <div v-if="items.length" class="space-y-2.5">
            <OpsPersonCard
                v-for="person in items"
                :key="person.id"
                :person="person"
                :badges="badgesFor(person)"
                :meta-tail="metaFor(person)"
            >
                <template #actions>
                    <a
                        v-if="waHref(person)"
                        :href="waHref(person)"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 px-3 py-2 text-[12px] font-bold text-emerald-700 hover:bg-emerald-100"
                    >
                        <i class="ti ti-brand-whatsapp text-sm" aria-hidden="true" /> Reach out
                    </a>
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
            title="No one's gone quiet"
            description="No artisans are inactive beyond this window right now."
            icon="ti ti-flame"
        />
    </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import OpsPersonCard from '@/Components/Admin/OpsPersonCard.vue';
import { waLink } from '@/utils/waLink';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    counts: { type: Object, default: () => ({}) },
    window: { type: String, default: '30' },
    windows: { type: Array, default: () => ['30', '60', '90'] },
});

const items = ref([...props.rows]);

watch(() => props.rows, (value) => {
    items.value = [...value];
});

const chips = computed(() => props.windows.map((key) => ({
    key,
    label: `${key}+ days quiet`,
    count: props.counts[key] ?? 0,
})));

const select = (key) => {
    if (key === props.window) return;
    router.get(route('admin.reengagement.index', { window: key }), {}, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const badgesFor = (person) => {
    const out = [];
    if (person.never_returned) {
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
    if (person.last_seen) bits.push(`Last seen ${person.last_seen}`);
    return bits.join(' · ');
};

const waHref = (person) => waLink(person.whatsapp, `Hi ${person.person || ''}, it's the Kraftrack team — we've missed you! Anything we can help with?`);
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
