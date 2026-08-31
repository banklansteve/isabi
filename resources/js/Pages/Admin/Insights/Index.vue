<template>
    <Head :title="team ? 'Ops insights' : 'My stats'" />

    <AdminChrome
        :title="team ? 'Ops insights' : 'My stats'"
        :eyebrow="team ? 'How the floor is performing' : 'Your completed work'"
    />

    <OpsPerformanceTabs />

    <div class="mb-4">
        <AdminRangePicker :range="range" />
    </div>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <article
            v-for="kpi in kpis"
            :key="kpi.label"
            class="rounded-2xl bg-white px-4 py-3.5 shadow-premium ring-1 ring-ink/[0.05]"
        >
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">{{ kpi.label }}</p>
            <p class="mt-1.5 font-editorial text-2xl font-semibold tracking-tight text-ink sm:text-3xl">{{ kpi.value }}</p>
            <p v-if="kpi.hint" class="mt-0.5 text-[12px] font-medium text-ink/40">{{ kpi.hint }}</p>
        </article>
    </div>

    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center">
        <div class="no-scrollbar flex gap-1.5 overflow-x-auto">
            <button
                v-for="queue in queues"
                :key="queue.key"
                type="button"
                class="shrink-0 rounded-full px-3 py-1.5 text-[12px] font-semibold"
                :class="filters.queue === queue.key ? 'bg-base-action text-white' : 'bg-white text-ink/50 ring-1 ring-ink/[0.06]'"
                @click="setFilter('queue', queue.key)"
            >
                {{ queue.label }}
            </button>
        </div>
        <div class="flex flex-1 gap-2">
            <input
                v-model="search"
                type="search"
                :placeholder="team ? 'Search staff…' : 'Search your work…'"
                class="min-w-0 flex-1 rounded-xl border border-ink/10 bg-white px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                @change="setFilter('q', search)"
            />
            <select
                :value="filters.sort"
                class="rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-[13px] font-semibold outline-none"
                @change="setFilter('sort', $event.target.value)"
            >
                <option value="score">Score</option>
                <option value="completed">Completed</option>
                <option value="actions">Volume</option>
                <option value="open">On now</option>
                <option value="away">Away</option>
                <option value="name">Name</option>
            </select>
        </div>
    </div>

    <AdminEmpty
        v-if="!people.length"
        title="No matching staff"
        description="Try another date range, queue, or search."
        icon="ti ti-chart-bar"
    />

    <div v-else class="space-y-3">
        <article
            v-for="person in people"
            :key="person.id"
            class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5"
        >
            <div class="flex items-start gap-3">
                <span
                    class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-tint text-[13px] font-bold text-deep"
                >
                    {{ person.initials }}
                    <span
                        class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full ring-2 ring-white"
                        :class="statusDot(person.live.status)"
                    />
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-[15px] font-bold text-ink">{{ person.name }}</h2>
                        <span
                            class="rounded-full px-2 py-0.5 text-[11px] font-bold"
                            :class="statusClass(person.live.status)"
                        >
                            {{ person.live.label }}
                            <template v-if="person.live.away_label"> · {{ person.live.away_label }}</template>
                        </span>
                    </div>
                    <p class="mt-0.5 truncate text-[12px] font-medium text-ink/40">
                        {{ person.roles?.map((role) => role.name).join(' · ') || person.uid }}
                    </p>
                </div>
                <div class="shrink-0 text-right">
                    <p class="font-editorial text-3xl font-semibold text-ink">{{ person.range.score.total }}</p>
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">
                        Grade {{ person.range.grade }}
                    </p>
                </div>
            </div>

            <dl class="mt-4 grid grid-cols-3 gap-2">
                <div class="rounded-xl bg-pale/70 px-3 py-2">
                    <dt class="text-[10px] font-bold uppercase tracking-[0.12em] text-ink/40">Today</dt>
                    <dd class="mt-0.5 text-sm font-bold text-ink">{{ person.today.completed }} done</dd>
                    <dd class="text-[11px] font-medium text-ink/40">{{ person.today.actions }} actions</dd>
                </div>
                <div class="rounded-xl bg-pale/70 px-3 py-2">
                    <dt class="text-[10px] font-bold uppercase tracking-[0.12em] text-ink/40">This week</dt>
                    <dd class="mt-0.5 text-sm font-bold text-ink">{{ person.this_week.completed }} done</dd>
                    <dd class="text-[11px] font-medium text-ink/40">{{ person.this_week.actions }} actions</dd>
                </div>
                <div class="rounded-xl bg-pale/70 px-3 py-2">
                    <dt class="text-[10px] font-bold uppercase tracking-[0.12em] text-ink/40">This month</dt>
                    <dd class="mt-0.5 text-sm font-bold text-ink">{{ person.this_month.completed }} done</dd>
                    <dd class="text-[11px] font-medium text-ink/40">{{ person.this_month.actions }} actions</dd>
                </div>
            </dl>

            <div class="mt-3 grid grid-cols-2 gap-2 text-[12px] font-semibold text-ink/55 sm:grid-cols-4">
                <p>{{ person.range.completed }} completed</p>
                <p>{{ person.range.chats_resolved }} chats closed</p>
                <p>{{ person.range.patrol_resolved }} patrol closed</p>
                <p>{{ responseLabel(person.range) }}</p>
            </div>

            <div
                v-if="person.live.attendance"
                class="mt-4 rounded-2xl bg-white p-4 ring-1 ring-ink/[0.06]"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-ink/35">Shift</p>
                        <p class="mt-1 text-[15px] font-bold tracking-tight text-ink">
                            {{ formatShiftClock(person.live.attendance.start) }}
                            <span class="font-medium text-ink/30">–</span>
                            {{ formatShiftClock(person.live.attendance.end) }}
                        </p>
                        <p class="mt-0.5 text-[12px] font-medium text-ink/40">
                            {{ person.live.attendance.days_label || 'No days set' }}
                        </p>
                    </div>
                    <span
                        class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold"
                        :class="shiftBadgeClass(person.live.attendance)"
                    >
                        {{ shiftBadgeLabel(person.live.attendance) }}
                    </span>
                </div>

                <div class="mt-3.5 grid grid-cols-7 gap-1">
                    <div
                        v-for="day in shiftWeekdays(person.live.attendance)"
                        :key="day.id"
                        class="flex flex-col items-center gap-1.5"
                    >
                        <span class="text-[10px] font-bold uppercase tracking-wide text-ink/30">
                            {{ day.short }}
                        </span>
                        <span
                            class="flex h-8 w-full items-center justify-center rounded-lg text-[11px] font-bold"
                            :class="day.on
                                ? 'bg-tint text-deep'
                                : 'bg-[#F4F6FA] text-ink/20'"
                        >
                            {{ day.on ? '•' : '–' }}
                        </span>
                    </div>
                </div>

                <div class="mt-3.5 grid grid-cols-3 gap-2">
                    <div class="rounded-xl bg-[#F4F6FA] px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-ink/35">In</p>
                        <p
                            class="mt-1 text-[13px] font-bold tabular-nums"
                            :class="person.live.attendance.missed ? 'text-coral-deep' : 'text-ink'"
                        >
                            {{ person.live.attendance.logged_in }}
                        </p>
                    </div>
                    <div class="rounded-xl bg-[#F4F6FA] px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-ink/35">Out</p>
                        <p class="mt-1 text-[13px] font-bold tabular-nums text-ink">
                            {{ person.live.attendance.logged_out }}
                        </p>
                    </div>
                    <div class="rounded-xl bg-[#F4F6FA] px-3 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-ink/35">Idle</p>
                        <p class="mt-1 text-[13px] font-bold tabular-nums text-ink">
                            {{ person.live.attendance.idle_label }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Score mix</p>
                <div class="mt-1.5 grid grid-cols-2 gap-1.5 sm:grid-cols-4">
                    <p
                        v-for="part in scoreParts(person.range.score)"
                        :key="part.label"
                        class="rounded-lg bg-[#F4F6FA] px-2 py-1.5 text-[11px] font-semibold text-ink/55"
                    >
                        {{ part.label }}
                        <span class="float-right font-bold text-ink">{{ part.value }}</span>
                    </p>
                </div>
            </div>

            <div v-if="person.live.tasks?.length" class="mt-4">
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Doing now</p>
                <ul class="mt-2 space-y-1.5">
                    <li v-for="task in person.live.tasks" :key="task.href">
                        <Link
                            :href="task.href"
                            class="flex items-center justify-between gap-2 rounded-xl bg-pale/80 px-3 py-2 text-[13px] font-semibold text-ink hover:bg-tint"
                        >
                            <span class="min-w-0 truncate">{{ task.label }}</span>
                            <span class="shrink-0 text-[11px] font-medium text-ink/40">{{ task.waiting }}</span>
                        </Link>
                    </li>
                </ul>
            </div>
            <p v-else class="mt-4 text-[13px] font-medium text-ink/35">
                No open assigned work right now.
            </p>
        </article>
    </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import AdminRangePicker from '@/Components/Admin/AdminRangePicker.vue';
import OpsPerformanceTabs from '@/Components/Admin/OpsPerformanceTabs.vue';
import { useDateRange } from '@/Composables/useDateRange';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    team: { type: Boolean, default: false },
    filters: { type: Object, default: () => ({}) },
    range_label: { type: String, default: '' },
    queues: { type: Array, default: () => [] },
    kpis: { type: Object, default: () => ({}) },
    staff: { type: Array, default: () => [] },
    poll_ms: { type: Number, default: 15000 },
    idle_after_minutes: { type: Number, default: 7 },
});

const people = ref([...props.staff]);
const search = ref(props.filters?.q || '');
const range = useDateRange(props.filters?.range || 'this_week');
range.customFrom.value = props.filters?.from || '';
range.customTo.value = props.filters?.to || '';
let skipRangeWatch = true;
let liveTimer = null;

const kpis = computed(() => [
    { label: 'Score', value: props.kpis.avg_score ?? 0, hint: props.team ? 'Team average' : 'This window' },
    { label: 'Completed', value: props.kpis.completed ?? 0, hint: props.range_label },
    { label: 'On now', value: props.kpis.open_tasks ?? 0, hint: 'Open assigned work' },
    {
        label: 'Away',
        value: props.kpis.away ?? 0,
        hint: `Idle over ${props.idle_after_minutes}m on duty`,
    },
]);

const visit = (overrides = {}) => {
    router.get(route('admin.insights.index'), {
        range: range.preset.value,
        from: range.customFrom.value || undefined,
        to: range.customTo.value || undefined,
        queue: props.filters.queue,
        q: search.value,
        sort: props.filters.sort,
        ...overrides,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const setFilter = (key, value) => visit({ [key]: value });

watch(
    [() => range.preset.value, () => range.customFrom.value, () => range.customTo.value],
    () => {
        if (skipRangeWatch) {
            skipRangeWatch = false;
            return;
        }
        visit();
    },
);

watch(() => props.staff, (value) => {
    people.value = [...value];
});

const mergeLive = (payload) => {
    const map = Object.fromEntries((payload.staff || []).map((row) => [row.id, row.live]));
    people.value = people.value.map((person) => (
        map[person.id] ? { ...person, live: map[person.id] } : person
    ));
};

const pollLive = async () => {
    try {
        const { data } = await axios.get(route('admin.insights.live'), { headers: { Accept: 'application/json' } });
        mergeLive(data);
    } catch {
        // Keep the last live strip if the poll fails.
    }
};

onMounted(() => {
    liveTimer = window.setInterval(pollLive, Number(props.poll_ms || 15000));
});
onUnmounted(() => window.clearInterval(liveTimer));

const statusDot = (status) => ({
    active: 'bg-emerald-500',
    idle: 'bg-amber-400',
    away: 'bg-coral',
    leave: 'bg-sky-400',
    off_duty: 'bg-ink/25',
    unknown: 'bg-ink/20',
}[status] || 'bg-ink/20');

const statusClass = (status) => ({
    active: 'bg-emerald-50 text-emerald-800',
    idle: 'bg-amber-50 text-amber-800',
    away: 'bg-coral/10 text-coral-deep',
    leave: 'bg-sky-50 text-sky-800',
    off_duty: 'bg-pale text-ink/45',
    unknown: 'bg-pale text-ink/45',
}[status] || 'bg-pale text-ink/45');

const responseLabel = (stats) => {
    if (stats.avg_first_response_minutes != null) {
        return `${stats.avg_first_response_minutes}m first reply`;
    }
    if (stats.csat != null) {
        return `CSAT ${stats.csat}`;
    }
    return 'No reply time yet';
};

const scoreParts = (score) => [
    { label: 'Done', value: score.completions },
    { label: 'Speed', value: score.responsiveness },
    { label: 'Volume', value: score.volume },
    { label: 'Presence', value: score.presence },
];

const SHIFT_DAYS = [
    { id: 1, short: 'Mo' },
    { id: 2, short: 'Tu' },
    { id: 3, short: 'We' },
    { id: 4, short: 'Th' },
    { id: 5, short: 'Fr' },
    { id: 6, short: 'Sa' },
    { id: 7, short: 'Su' },
];

const shiftWeekdays = (attendance) => {
    const on = new Set(attendance?.days || []);

    return SHIFT_DAYS.map((day) => ({ ...day, on: on.has(day.id) }));
};

const formatShiftClock = (value) => {
    const match = String(value || '').match(/^(\d{1,2}):(\d{2})/);
    if (!match) {
        return value || '—';
    }

    return `${match[1].padStart(2, '0')}:${match[2]}`;
};

const shiftBadgeLabel = (attendance) => {
    if (attendance?.late) {
        return 'Late';
    }
    if (attendance?.missed) {
        return 'Missed';
    }
    if (attendance?.on_duty_now) {
        return 'On shift';
    }

    return attendance?.on_duty_today ? 'Off floor' : 'Off today';
};

const shiftBadgeClass = (attendance) => {
    if (attendance?.late) {
        return 'bg-amber-50 text-amber-800';
    }
    if (attendance?.missed) {
        return 'bg-coral/10 text-coral-deep';
    }
    if (attendance?.on_duty_now) {
        return 'bg-emerald-50 text-emerald-800';
    }

    return 'bg-[#F4F6FA] text-ink/45';
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
