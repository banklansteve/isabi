<template>
    <Head title="Leave calendar" />

    <AdminChrome title="Leave calendar" eyebrow="Who is out" />
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <button type="button" class="tap-target flex h-9 w-9 items-center justify-center rounded-xl bg-white text-ink/55 shadow-premium ring-1 ring-ink/[0.06] transition-colors hover:text-ink" aria-label="Previous month" @click="visitAdmin(route('admin.hr.calendar', { month: prevMonth }))">
                    <i class="ti ti-chevron-left" aria-hidden="true" />
                </button>
                <p class="min-w-[9rem] text-center text-base font-semibold text-ink">{{ monthLabel }}</p>
                <button type="button" class="tap-target flex h-9 w-9 items-center justify-center rounded-xl bg-white text-ink/55 shadow-premium ring-1 ring-ink/[0.06] transition-colors hover:text-ink" aria-label="Next month" @click="visitAdmin(route('admin.hr.calendar', { month: nextMonth }))">
                    <i class="ti ti-chevron-right" aria-hidden="true" />
                </button>
            </div>
            <button type="button" class="text-sm font-semibold text-base-action hover:text-base-hover" @click="visitAdmin(route('admin.hr.calendar'))">Today</button>
        </div>

        <div class="mt-5 overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.06]">
            <div class="grid grid-cols-7 border-b border-ink/10 bg-pale/60">
                <span v-for="day in weekdays" :key="day" class="py-2.5 text-center text-[11px] font-bold uppercase tracking-wide text-ink/40">{{ day }}</span>
            </div>
            <div class="grid grid-cols-7">
                <div
                    v-for="(cell, i) in cells"
                    :key="i"
                    class="min-h-[6rem] border-b border-r border-ink/[0.06] p-1.5 last:border-r-0"
                    :class="cell.currentMonth ? 'bg-white' : 'bg-pale/40'"
                >
                    <div class="mb-1 flex items-center justify-between">
                        <span
                            class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold"
                            :class="cell.isToday ? 'bg-base-action text-white' : cell.currentMonth ? 'text-ink/70' : 'text-ink/25'"
                        >{{ cell.day }}</span>
                    </div>
                    <div class="space-y-1">
                        <div
                            v-for="ev in cell.events.slice(0, 3)"
                            :key="ev.id"
                            class="truncate rounded-md px-1.5 py-0.5 text-[11px] font-semibold"
                            :class="[chipClass(ev.color), ev.status === 'pending' ? 'opacity-70 ring-dashed' : '']"
                            :title="`${ev.staff_name} — ${ev.leave_type}${ev.status === 'pending' ? ' (pending)' : ''}`"
                        >
                            {{ ev.staff_name }}
                        </div>
                        <p v-if="cell.events.length > 3" class="px-1 text-[11px] font-semibold text-ink/40">+{{ cell.events.length - 3 }} more</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-4">
            <span class="text-xs font-semibold uppercase tracking-wide text-ink/40">Legend</span>
            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-ink/60"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-500" />Approved</span>
            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-ink/60"><span class="h-2.5 w-2.5 rounded-sm border border-dashed border-amber-500 bg-amber-100" />Pending</span>
        </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { leaveColorClasses } from '@/utils/hrStatus';
import { visitAdmin } from '@/utils/adminVisit';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    events: { type: Array, default: () => [] },
    month: { type: String, required: true },
    month_label: { type: String, required: true },
    prev_month: { type: String, required: true },
    next_month: { type: String, required: true },
});

const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const monthLabel = computed(() => props.month_label);
const prevMonth = computed(() => props.prev_month);
const nextMonth = computed(() => props.next_month);

const chipClass = (token) => leaveColorClasses(token);

const toISO = (d) => {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
};

const cells = computed(() => {
    const [year, month] = props.month.split('-').map(Number);
    const first = new Date(year, month - 1, 1);
    const startPad = first.getDay();
    const daysInMonth = new Date(year, month, 0).getDate();
    const todayIso = toISO(new Date());
    const items = [];

    const push = (date, currentMonth) => {
        const iso = toISO(date);
        const events = props.events.filter((ev) => iso >= ev.start_date && iso <= ev.end_date);
        items.push({ day: date.getDate(), iso, currentMonth, isToday: iso === todayIso, events });
    };

    for (let i = 0; i < startPad; i++) {
        push(new Date(year, month - 1, i - startPad + 1), false);
    }
    for (let d = 1; d <= daysInMonth; d++) {
        push(new Date(year, month - 1, d), true);
    }
    while (items.length % 7 !== 0) {
        const last = items[items.length - 1];
        const [ly, lm, ld] = last.iso.split('-').map(Number);
        push(new Date(ly, lm - 1, ld + 1), false);
    }

    return items;
});
</script>
