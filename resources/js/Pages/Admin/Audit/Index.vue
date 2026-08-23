<template>
    <Head title="Audit log" />

    <AdminChrome title="Audit log" eyebrow="Every admin action" />
        <div class="mb-4 flex flex-col gap-3">
            <AdminRangePicker :range="range" />
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <input
                    v-model="list.q.value"
                    type="search"
                    placeholder="Search actions, people, summaries…"
                    class="w-full rounded-xl border border-ink/10 bg-white px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15 sm:max-w-sm"
                />
                <select
                    v-model="action"
                    class="rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                >
                    <option value="">All actions</option>
                    <option v-for="item in actions" :key="item" :value="item">{{ item }}</option>
                </select>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
            <AdminEmpty
                v-if="!list.pageItems.value.length"
                title="No admin actions yet"
                description="Pricing edits, suspensions, refunds, and settings changes will appear here with old → new values."
                icon="ti ti-history"
            />
            <ul v-else class="divide-y divide-ink/[0.06]">
                <li v-for="log in list.pageItems.value" :key="log.id" class="px-4 py-4 sm:px-5">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                        <p class="text-sm font-bold text-ink">{{ log.summary }}</p>
                        <p class="text-[11px] font-medium text-ink/35" :title="log.when">{{ log.relative }}</p>
                    </div>
                    <p class="mt-1 text-[12px] font-medium text-ink/40">
                        {{ log.actor?.name || 'System' }}
                        <span class="mx-1.5 font-mono text-ink/30">{{ log.action }}</span>
                    </p>
                    <div
                        v-if="hasDiff(log)"
                        class="mt-3 grid gap-2 rounded-xl bg-pale/80 p-3 text-[12px] sm:grid-cols-2"
                    >
                        <div>
                            <p class="mb-1 font-bold uppercase tracking-wide text-ink/35">Before</p>
                            <pre class="overflow-x-auto whitespace-pre-wrap font-mono text-[11px] leading-relaxed text-ink/60">{{ pretty(log.old_values) }}</pre>
                        </div>
                        <div>
                            <p class="mb-1 font-bold uppercase tracking-wide text-ink/35">After</p>
                            <pre class="overflow-x-auto whitespace-pre-wrap font-mono text-[11px] leading-relaxed text-ink/60">{{ pretty(log.new_values) }}</pre>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <AdminClientPager
            :page="list.page.value"
            :pages="list.pageCount.value"
            :total="list.total.value"
            :per-page="list.perPage"
            @update:page="list.page.value = $event"
        />
</template>

<script setup>
import AdminClientPager from '@/Components/Admin/AdminClientPager.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import AdminRangePicker from '@/Components/Admin/AdminRangePicker.vue';
import { useClientList } from '@/Composables/useClientList';
import { useDateRange } from '@/Composables/useDateRange';
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    logs: { type: Array, default: () => [] },
    actions: { type: Array, default: () => [] },
});

const range = useDateRange('all');
const action = ref('');

const list = useClientList(
    () => props.logs.filter((log) => {
        if (action.value && log.action !== action.value) return false;
        return range.matches(log.created_iso);
    }),
    {
        perPage: 30,
        searchFields: ['summary', 'action', 'actor.name', 'actor.email'],
        sort: 'date_desc',
        sortMap: { date: 'created_iso' },
    },
);

watch([action, () => range.preset.value], () => {
    list.page.value = 1;
});

const hasDiff = (log) =>
    (log.old_values && Object.keys(log.old_values).length) ||
    (log.new_values && Object.keys(log.new_values).length);

const pretty = (value) => {
    if (value == null || value === '') return '—';
    if (typeof value === 'string') return value;
    try {
        return JSON.stringify(value, null, 2);
    } catch {
        return String(value);
    }
};
</script>
