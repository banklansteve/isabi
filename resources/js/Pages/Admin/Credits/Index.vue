<template>
    <Head title="Credits" />

    <AdminChrome title="Credits & transactions" eyebrow="Token ledger" />
        <form
            v-if="isSuper"
            class="mb-4 grid gap-2 rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:grid-cols-4 sm:p-5"
            @submit.prevent="adjust"
        >
            <input v-model="adjustForm.email" type="email" placeholder="Artisan email" class="rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15" />
            <input v-model="adjustForm.amount" type="number" min="1" placeholder="Amount" class="rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15" />
            <select v-model="adjustForm.direction" class="rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium">
                <option value="credit">Credit</option>
                <option value="debit">Debit / refund</option>
            </select>
            <button type="submit" class="rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-base-hover disabled:opacity-50" :disabled="adjustForm.processing">
                Apply
            </button>
            <input v-model="adjustForm.reason" placeholder="Reason (required)" class="rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15 sm:col-span-4" />
            <p v-if="adjustForm.errors.email" class="text-xs font-semibold text-red-500 sm:col-span-4">{{ adjustForm.errors.email }}</p>
        </form>

        <div class="mb-4 flex flex-col gap-3">
            <AdminRangePicker :range="range" />
            <input
                v-model="list.q.value"
                type="search"
                placeholder="Search by user, reference, description…"
                class="w-full rounded-xl border border-ink/10 bg-white px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15 sm:max-w-md"
            />
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
            <AdminEmpty
                v-if="!list.pageItems.value.length"
                title="No movements yet"
                description="Purchases, spends, referrals, and admin adjustments appear here."
                icon="ti ti-coin"
            />
            <ul v-else class="divide-y divide-ink/[0.06]">
                <li v-for="row in list.pageItems.value" :key="`${row.kind}-${row.id}`" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3.5 sm:px-5">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-ink">{{ row.user?.name || row.pack_name }}</p>
                        <p class="text-[13px] font-medium text-ink/45">
                            {{ row.description || row.reference }} · {{ row.when }}
                        </p>
                    </div>
                    <p class="text-sm font-bold tabular-nums" :class="row.type === 'debit' ? 'text-coral' : 'text-ink'">
                        <template v-if="row.price">₦{{ Number(row.price).toLocaleString() }}</template>
                        <template v-else>{{ row.type === 'debit' ? '−' : '+' }}{{ row.amount }}</template>
                    </p>
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
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    purchases: { type: Array, default: () => [] },
    transactions: { type: Array, default: () => [] },
});

const isSuper = computed(() => !!usePage().props.auth?.user?.is_super_admin);
const { tab } = useAdminTabs({ tab: 'transactions' });
const range = useDateRange('all');

const source = computed(() => (tab.value === 'purchases' ? props.purchases : props.transactions));

const list = useClientList(
    () => source.value.filter((row) => range.matches(row.created_iso)),
    {
        perPage: 24,
        searchFields: ['description', 'reference', 'pack_name', 'user.name', 'user.email', 'action'],
        sort: 'date_desc',
        sortMap: { date: 'created_iso' },
    },
);

watch([tab, () => range.preset.value], () => {
    list.page.value = 1;
});

const adjustForm = useForm({
    email: '',
    amount: 5,
    direction: 'credit',
    reason: '',
});

const adjust = () => adjustForm.post(route('admin.credits.adjust'), { preserveScroll: true });
</script>
