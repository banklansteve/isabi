<template>
    <Head title="Job logs" />

    <AdminChrome title="Job logs" eyebrow="Platform work" />
        <div class="mb-4 flex flex-col gap-3">
            <AdminRangePicker :range="range" />
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <input
                    v-model="list.q.value"
                    type="search"
                    placeholder="Search jobs, clients, artisans…"
                    class="w-full rounded-xl border border-ink/10 bg-white px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15 sm:max-w-md"
                />
                <select v-model="list.sort.value" class="rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15">
                    <option value="date_desc">Newest</option>
                    <option value="date_asc">Oldest</option>
                </select>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
            <AdminEmpty
                v-if="!list.pageItems.value.length"
                title="No jobs here"
                description="Logged jobs across Isabi will show up in this list."
                icon="ti ti-briefcase"
            />
            <ul v-else class="divide-y divide-ink/[0.06]">
                <li v-for="job in list.pageItems.value" :key="job.id" class="px-4 py-4 sm:px-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-ink">{{ job.description }}</p>
                            <p class="mt-1 text-[13px] font-medium text-ink/45">
                                {{ job.user?.name }} · {{ job.client_name || 'No client' }} · {{ job.worked_on }}
                            </p>
                            <p v-if="job.backdated_days >= 30" class="mt-1 text-[12px] font-semibold text-coral">
                                Backdated {{ job.backdated_days }} days
                            </p>
                        </div>
                        <button
                            type="button"
                            class="rounded-xl px-3 py-2 text-xs font-bold transition-colors active:scale-[0.98]"
                            :class="job.flagged ? 'bg-coral-tint text-coral-deep' : 'bg-pale text-ink/60 hover:bg-tint'"
                            @click="toggleFlag(job)"
                        >
                            {{ job.flagged ? 'Clear flag' : 'Flag' }}
                        </button>
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
import { useAdminTabs } from '@/Composables/useAdminTabs';
import { useClientList } from '@/Composables/useClientList';
import { useDateRange } from '@/Composables/useDateRange';
import { toast } from '@/utils/adminRange';
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, watch } from 'vue';

const props = defineProps({
    jobs: { type: Array, default: () => [] },
});

const { tab } = useAdminTabs({ tab: 'all' });
const range = useDateRange('all');
const rows = ref([...props.jobs]);

watch(
    () => props.jobs,
    (value) => {
        rows.value = [...value];
    },
);

const list = useClientList(
    () => rows.value.filter((job) => {
        if (tab.value === 'flagged' && !job.flagged) return false;
        if (tab.value === 'suspicious' && job.backdated_days < 30) return false;
        return range.matches(job.created_iso);
    }),
    {
        perPage: 20,
        searchFields: ['description', 'client_name', 'user.name', 'user.email'],
        sort: 'date_desc',
        sortMap: { date: 'created_iso' },
    },
);

watch([tab, () => range.preset.value], () => {
    list.page.value = 1;
});

const toggleFlag = async (job) => {
    const url = job.flagged ? route('admin.jobs.unflag', job.uid) : route('admin.jobs.flag', job.uid);
    const { data } = await axios.post(url, job.flagged ? {} : { reason: 'Flagged from admin console' });
    job.flagged = !job.flagged;
    toast(data.toast || { type: 'success', title: 'Updated', message: 'Job moderation updated.' });
};
</script>
