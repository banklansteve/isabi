<template>
    <Head title="Audit log" />

    <AdminChrome title="Audit log" eyebrow="Staff admin actions" />
        <form class="mb-4 rounded-2xl bg-white p-3 shadow-premium ring-1 ring-ink/[0.05] sm:p-4" @submit.prevent="applyFilters">
            <div class="flex flex-col gap-3">
                <div class="relative min-w-0">
                    <i class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/30" aria-hidden="true" />
                    <input
                        v-model="form.q"
                        type="search"
                        placeholder="Search by staff name or email…"
                        class="w-full rounded-xl border border-ink/10 bg-[#F4F6FA] py-2.5 ps-10 pe-4 text-sm font-medium outline-none focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                    />
                </div>
                <div class="flex flex-col gap-2 lg:flex-row lg:items-end">
                    <label class="min-w-0 flex-1 text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">
                        From
                        <input
                            v-model="form.from"
                            type="date"
                            class="mt-1.5 w-full rounded-xl border border-ink/10 bg-[#F4F6FA] px-3 py-2.5 text-sm font-medium text-ink outline-none focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                        />
                    </label>
                    <label class="min-w-0 flex-1 text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">
                        To
                        <input
                            v-model="form.to"
                            type="date"
                            class="mt-1.5 w-full rounded-xl border border-ink/10 bg-[#F4F6FA] px-3 py-2.5 text-sm font-medium text-ink outline-none focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                        />
                    </label>
                    <label class="min-w-0 flex-1 text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">
                        Action
                        <select
                            v-model="form.action"
                            class="mt-1.5 w-full rounded-xl border border-ink/10 bg-[#F4F6FA] px-3 py-2.5 text-sm font-medium text-ink outline-none focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                        >
                            <option value="">All actions</option>
                            <option v-for="item in actions" :key="item.value" :value="item.value">{{ item.label }}</option>
                        </select>
                    </label>
                    <FormButton type="submit" variant="primary" label="Apply" :loading="form.processing" loading-label="Loading…" />
                </div>
                <div class="no-scrollbar flex gap-1.5 overflow-x-auto">
                    <button
                        v-for="chip in chips"
                        :key="chip.id"
                        type="button"
                        class="shrink-0 rounded-full px-3 py-1.5 text-[12px] font-semibold transition-all duration-150 active:scale-[0.97]"
                        :class="chipActive(chip) ? 'bg-base-action text-white shadow-sm' : 'bg-[#F4F6FA] text-ink/50 ring-1 ring-ink/[0.06] hover:bg-tint hover:text-deep'"
                        @click="applyChip(chip)"
                    >
                        {{ chip.label }}
                    </button>
                </div>
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
            <AdminEmpty
                v-if="rows.length === 0"
                title="No matching actions"
                description="This view starts on today. Search a staff name or email, or widen the dates."
                icon="ti ti-history"
            />
            <ul v-else class="divide-y divide-ink/[0.06]">
                <li v-for="log in rows" :key="log.id" class="px-4 py-4 sm:px-5">
                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-1">
                        <div class="min-w-0">
                            <p class="text-sm font-bold tracking-tight text-ink">{{ log.action_label }}</p>
                            <p class="mt-1 text-sm font-medium leading-relaxed text-ink/65">{{ log.summary }}</p>
                        </div>
                        <p class="shrink-0 text-[12px] font-semibold text-ink/45" :title="log.when_full">
                            {{ log.when }}
                        </p>
                    </div>
                    <p class="mt-2 text-[12px] font-medium text-ink/40">
                        <template v-if="log.actor">{{ log.actor.name }} · {{ log.actor.email }}</template>
                        <template v-else>System</template>
                    </p>
                    <ul v-if="log.changes.length" class="mt-3 space-y-1.5 rounded-xl bg-pale/80 px-3.5 py-3">
                        <li v-for="change in log.changes" :key="change.label" class="text-[13px] font-medium leading-relaxed text-ink/70">
                            <span class="font-semibold text-ink/50">{{ change.label }}:</span>
                            {{ change.from }}
                            <span class="mx-1 text-ink/30">→</span>
                            {{ change.to }}
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
        <AdminClientPager
            :page="logs.current_page || 1"
            :pages="logs.last_page || 1"
            :total="logs.total || 0"
            :per-page="logs.per_page || 25"
            @update:page="goToPage"
        />
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminClientPager from '@/Components/Admin/AdminClientPager.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import { visitAdmin } from '@/utils/adminVisit';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, required: true },
    actions: { type: Array, default: () => [] },
});

const rows = computed(() => props.logs.data || []);

const form = useForm({
    q: props.filters.q || '',
    action: props.filters.action || '',
    from: props.filters.from || '',
    to: props.filters.to || '',
});

const isoDate = (date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

const addDays = (iso, days) => {
    const date = new Date(`${iso}T00:00:00`);
    date.setDate(date.getDate() + days);
    return isoDate(date);
};

const todayIso = computed(() => props.filters.today || isoDate(new Date()));

const chips = computed(() => [
    { id: 'today', label: 'Today', from: todayIso.value, to: todayIso.value },
    { id: 'last_7', label: 'Last 7 days', from: addDays(todayIso.value, -6), to: todayIso.value },
    { id: 'last_30', label: 'Last 30 days', from: addDays(todayIso.value, -29), to: todayIso.value },
]);

const chipActive = (chip) => form.from === chip.from && form.to === chip.to;

const query = (page) => ({
    q: form.q || undefined,
    action: form.action || undefined,
    from: form.from || undefined,
    to: form.to || undefined,
    page: page && page > 1 ? page : undefined,
});

const applyFilters = () => {
    form.get(route('admin.audit.index'), {
        preserveState: true,
        replace: true,
    });
};

const applyChip = (chip) => {
    form.from = chip.from;
    form.to = chip.to;
    applyFilters();
};

const goToPage = (page) => {
    visitAdmin(route('admin.audit.index', query(page)), { preserveScroll: true });
};
</script>
