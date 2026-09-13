<template>
    <Head title="Onboarding follow-up" />

    <AdminChrome title="Onboarding follow-up" eyebrow="Growth &amp; lifecycle" />

    <div class="space-y-5">
        <header class="flex flex-col gap-1">
            <h1 class="font-editorial text-[1.6rem] font-semibold leading-tight tracking-tight text-ink sm:text-[1.9rem]">
                Onboarding follow-up
            </h1>
            <p class="max-w-2xl text-[13px] font-medium leading-relaxed text-ink/50">
                Reach out to artisans stuck mid-funnel. Each tab is a drop-off point — nudge them to the next step before they go cold.
            </p>
        </header>

        <div class="no-scrollbar -mx-1 flex gap-2 overflow-x-auto px-1 pb-1">
            <button
                v-for="chip in chips"
                :key="chip.key"
                type="button"
                class="flex shrink-0 items-center gap-2 rounded-full px-3.5 py-2 text-[13px] font-semibold transition-all duration-150 active:scale-[0.97]"
                :class="segment === chip.key ? 'bg-base-action text-white shadow-sm' : 'bg-white text-ink/55 ring-1 ring-ink/[0.06] hover:text-deep'"
                @click="select(chip.key)"
            >
                {{ chip.label }}
                <span
                    class="rounded-full px-1.5 py-0.5 text-[10px] font-bold tabular-nums"
                    :class="segment === chip.key ? 'bg-white/25 text-white' : 'bg-pale text-ink/45'"
                >
                    {{ chip.count }}
                </span>
            </button>
        </div>

        <p class="rounded-xl bg-tint/60 px-3.5 py-2.5 text-[12px] font-semibold text-deep">
            <i class="ti ti-bulb mr-1" aria-hidden="true" />{{ hint }}
        </p>

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
                        <i class="ti ti-brand-whatsapp text-sm" aria-hidden="true" /> Nudge
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
            title="No one stuck here"
            description="Nobody is sitting at this drop-off point right now."
            icon="ti ti-route"
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
    segment: { type: String, default: 'no_job' },
});

const items = ref([...props.rows]);

watch(() => props.rows, (value) => {
    items.value = [...value];
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
}[props.segment] || ''));

const select = (key) => {
    if (key === props.segment) return;
    router.get(route('admin.onboarding.index', { segment: key }), {}, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
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

const waHref = (person) => waLink(person.whatsapp, `Hi ${person.person || ''}, it's the Kraftrack team — need a hand getting your first job logged?`);
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
