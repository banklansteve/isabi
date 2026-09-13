<template>
    <Head title="Verification" />

    <AdminChrome title="Verification" eyebrow="Growth &amp; lifecycle" />

    <div class="space-y-5">
        <header class="flex flex-col gap-1">
            <h1 class="font-editorial text-[1.6rem] font-semibold leading-tight tracking-tight text-ink sm:text-[1.9rem]">
                Verification queue
            </h1>
            <p class="max-w-2xl text-[13px] font-medium leading-relaxed text-ink/50">
                Review new sign-ups’ trade claims and confirm WhatsApp numbers. Verifying marks the account and clears it from this queue.
            </p>
        </header>

        <div class="no-scrollbar -mx-1 flex gap-2 overflow-x-auto px-1 pb-1">
            <button
                v-for="chip in chips"
                :key="chip.key"
                type="button"
                class="flex shrink-0 items-center gap-2 rounded-full px-3.5 py-2 text-[13px] font-semibold transition-all duration-150 active:scale-[0.97]"
                :class="filter === chip.key ? 'bg-base-action text-white shadow-sm' : 'bg-white text-ink/55 ring-1 ring-ink/[0.06] hover:text-deep'"
                @click="select(chip.key)"
            >
                {{ chip.label }}
                <span
                    class="rounded-full px-1.5 py-0.5 text-[10px] font-bold tabular-nums"
                    :class="filter === chip.key ? 'bg-white/25 text-white' : 'bg-pale text-ink/45'"
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
                        class="inline-flex items-center gap-1.5 rounded-xl bg-pale px-3 py-2 text-[12px] font-bold text-ink/60 hover:bg-tint hover:text-deep"
                    >
                        <i class="ti ti-brand-whatsapp text-sm" aria-hidden="true" /> WhatsApp
                    </a>
                    <Link
                        :href="route('admin.users.show', person.id)"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-pale px-3 py-2 text-[12px] font-bold text-ink/60 hover:bg-tint hover:text-deep"
                    >
                        <i class="ti ti-user-search text-sm" aria-hidden="true" /> Open
                    </Link>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-base-action px-3 py-2 text-[12px] font-bold text-white hover:bg-base-hover disabled:opacity-50"
                        :disabled="busyId === person.id"
                        @click="verify(person)"
                    >
                        <i class="ti ti-rosette-discount-check text-sm" aria-hidden="true" />
                        {{ busyId === person.id ? 'Verifying…' : 'Verify' }}
                    </button>
                </template>
            </OpsPersonCard>
        </div>

        <AdminEmpty
            v-else
            class="rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]"
            title="Nothing to verify"
            description="No accounts match this filter right now. Great work keeping the queue clear."
            icon="ti ti-rosette-discount-check"
        />
    </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import OpsPersonCard from '@/Components/Admin/OpsPersonCard.vue';
import { toast } from '@/utils/adminRange';
import { waLink } from '@/utils/waLink';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    counts: { type: Object, default: () => ({}) },
    filter: { type: String, default: 'all' },
});

const items = ref([...props.rows]);
const busyId = ref(null);

watch(() => props.rows, (value) => {
    items.value = [...value];
});

const chips = computed(() => [
    { key: 'all', label: 'All unverified', count: props.counts.all ?? 0 },
    { key: 'active', label: 'Already active', count: props.counts.active ?? 0 },
    { key: 'missing_whatsapp', label: 'No WhatsApp', count: props.counts.missing_whatsapp ?? 0 },
    { key: 'missing_trade', label: 'No trade', count: props.counts.missing_trade ?? 0 },
]);

const select = (key) => {
    if (key === props.filter) return;
    router.get(route('admin.verification.index', { filter: key }), {}, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const badgesFor = (person) => {
    const out = [];
    if (person.jobs > 0) {
        out.push({ label: `${person.jobs} job${person.jobs === 1 ? '' : 's'}`, class: 'bg-emerald-50 text-emerald-700' });
    }
    if (person.missing_whatsapp) {
        out.push({ label: 'No WhatsApp', class: 'bg-amber-50 text-amber-700' });
    }
    if (person.missing_trade) {
        out.push({ label: 'No trade', class: 'bg-amber-50 text-amber-700' });
    }
    return out;
};

const metaFor = (person) => {
    const bits = [];
    if (person.state) bits.push(person.state);
    if (person.joined) bits.push(`Joined ${person.joined}`);
    return bits.join(' · ');
};

const waHref = (person) => waLink(person.whatsapp, `Hi ${person.person || ''}, this is the Kraftrack team confirming your account details.`);

const verify = async (person) => {
    busyId.value = person.id;
    try {
        const { data } = await axios.post(route('admin.users.verify', person.id), {
            reason: 'Verified from the verification queue',
        });
        toast(data.toast);
        items.value = items.value.filter((row) => row.id !== person.id);
    } catch (error) {
        toast({
            type: 'error',
            title: 'Couldn’t verify',
            message: error.response?.data?.message || 'Try again.',
        });
    } finally {
        busyId.value = null;
    }
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
