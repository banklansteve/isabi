<template>
    <Head title="User activity" />

    <AdminChrome title="User activity" eyebrow="Platform events" />
        <div class="mb-4 flex flex-col gap-3">
            <AdminRangePicker :range="range" />
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative min-w-0 flex-1 sm:max-w-sm">
                    <i class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/30" aria-hidden="true" />
                    <input
                        v-model="list.q.value"
                        type="search"
                        placeholder="Search summaries, actions, people…"
                        class="w-full rounded-xl border border-ink/10 bg-white py-2.5 ps-10 pe-4 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                    />
                </div>
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
                title="No activity yet"
                description="Sign-ins, cookie choices, and other artisan actions will appear here."
                icon="ti ti-activity"
            />
            <ul v-else class="divide-y divide-ink/[0.06]">
                <li v-for="log in list.pageItems.value" :key="log.id" class="flex gap-3.5 px-4 py-4 sm:px-5">
                    <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-tint text-base text-deep">
                        <i :class="log.icon" aria-hidden="true" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                            <p class="text-sm font-semibold tracking-tight text-ink">{{ log.title }}</p>
                            <p class="text-[11px] font-medium text-ink/35" :title="log.created_at_human">
                                {{ log.relative }}
                            </p>
                        </div>
                        <p class="mt-0.5 text-sm font-medium leading-relaxed text-ink/55">{{ log.summary }}</p>
                        <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[11px] font-medium text-ink/35">
                            <span v-if="log.user">{{ log.user.name }} · {{ log.user.email }}</span>
                            <span v-else>Guest / anonymous</span>
                            <span v-if="log.ip_address">IP {{ log.ip_address }}</span>
                            <span class="rounded bg-pale px-1.5 py-0.5 font-mono text-ink/45">{{ log.action }}</span>
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
        searchFields: ['title', 'summary', 'action', 'user.name', 'user.email', 'ip_address'],
        sort: 'date_desc',
        sortMap: { date: 'created_iso' },
    },
);

watch([action, () => range.preset.value], () => {
    list.page.value = 1;
});
</script>
