<template>
    <Head title="HR" />

    <AdminChrome title="HR" :eyebrow="`${stats.total} people`" />
        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div
                v-for="card in statCards"
                :key="card.label"
                class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05]"
            >
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/30">{{ card.label }}</p>
                <p class="mt-1.5 text-2xl font-bold tracking-tight" :class="card.tone">{{ card.value }}</p>
            </div>
        </div>

        <form
            class="mb-4 rounded-2xl bg-white p-3 shadow-premium ring-1 ring-ink/[0.05] sm:p-4"
            @submit.prevent="applyFilters"
        >
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <div class="relative min-w-0 flex-1">
                    <i class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/30" aria-hidden="true" />
                    <input
                        v-model="form.q"
                        type="search"
                        placeholder="Search name or email…"
                        class="w-full rounded-xl border border-ink/10 bg-[#F4F6FA] py-2.5 ps-10 pe-4 text-sm font-medium outline-none transition-[box-shadow,border-color] duration-150 focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                    />
                </div>
                <div class="no-scrollbar flex gap-1.5 overflow-x-auto lg:justify-end">
                    <select
                        v-model="form.status"
                        class="rounded-full border border-ink/10 bg-[#F4F6FA] px-3 py-2 text-[13px] font-semibold text-ink outline-none focus:border-base"
                    >
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="on_leave">On leave</option>
                        <option value="exited">Exited</option>
                        <option value="unmanaged">No HR profile</option>
                    </select>
                    <select
                        v-if="departments.length"
                        v-model="form.department"
                        class="rounded-full border border-ink/10 bg-[#F4F6FA] px-3 py-2 text-[13px] font-semibold text-ink outline-none focus:border-base"
                    >
                        <option value="">All departments</option>
                        <option v-for="dept in departments" :key="dept" :value="dept">{{ dept }}</option>
                    </select>
                    <button
                        type="submit"
                        class="tap-target rounded-full bg-base-action px-4 py-2 text-[13px] font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                    >
                        Filter
                    </button>
                </div>
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
            <p
                v-if="members.length === 0"
                class="px-6 py-16 text-center text-sm font-medium text-ink/45"
            >
                No staff match this view.
            </p>
            <ul v-else class="divide-y divide-ink/10">
                <li v-for="member in members" :key="member.id">
                    <Link
                        :href="route('admin.hr.staff.show', member.id)"
                        class="flex items-center gap-3.5 px-5 py-4 transition-colors hover:bg-pale/70 sm:px-6"
                    >
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-pale text-xs font-bold text-deep">
                            {{ member.initials }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate text-sm font-semibold tracking-tight text-ink">
                                    {{ member.name }}
                                </p>
                                <span
                                    v-if="member.on_leave"
                                    class="hidden shrink-0 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800 sm:inline"
                                >
                                    On leave
                                </span>
                            </div>
                            <p class="truncate text-xs font-medium text-ink/45">
                                {{ member.position || member.role_label }}
                                <span v-if="member.department"> · {{ member.department }}</span>
                            </p>
                        </div>
                        <span
                            class="hidden text-xs font-medium text-ink/40 sm:block"
                        >
                            {{ member.start_date_label || '—' }}
                        </span>
                        <span :class="[pillBase, statusMeta(member.display_status).class, 'shrink-0']">
                            <span class="h-1.5 w-1.5 rounded-full" :class="statusMeta(member.display_status).dot" />
                            {{ statusMeta(member.display_status).label }}
                        </span>
                        <i class="ti ti-chevron-right shrink-0 text-ink/25" aria-hidden="true" />
                    </Link>
                </li>
            </ul>
        </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { employmentStatusMeta, pillBase } from '@/utils/hrStatus';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    members: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
    stats: { type: Object, required: true },
    can: { type: Object, default: () => ({}) },
});

const form = useForm({
    q: props.filters.q || '',
    status: props.filters.status || '',
    department: props.filters.department || '',
});

const statCards = computed(() => [
    { label: 'Total staff', value: props.stats.total, tone: 'text-ink' },
    { label: 'Active', value: props.stats.active, tone: 'text-emerald-600' },
    { label: 'On leave', value: props.stats.on_leave, tone: 'text-amber-600' },
    { label: 'Pending leave', value: props.stats.pending_leave, tone: 'text-coral-deep' },
]);

const statusMeta = (status) => employmentStatusMeta(status);

const applyFilters = () => {
    router.get(
        route('admin.hr.index'),
        {
            q: form.q || undefined,
            status: form.status || undefined,
            department: form.department || undefined,
        },
        { preserveState: true, replace: true, showProgress: false },
    );
};
</script>
