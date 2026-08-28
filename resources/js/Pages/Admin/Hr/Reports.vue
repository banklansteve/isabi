<template>
    <Head title="HR reports" />

    <AdminChrome title="HR reports" eyebrow="Headcount, leave, coverage" />
        <!-- Headcount -->
        <section class="rounded-[1.5rem] bg-white p-6 shadow-premium ring-1 ring-ink/[0.06]">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Headcount</h2>
                <a :href="route('admin.hr.reports.export', 'headcount')" class="inline-flex items-center gap-1.5 text-xs font-semibold text-base-action hover:text-base-hover"><i class="ti ti-download" aria-hidden="true" />CSV</a>
            </div>
            <div class="mt-4 grid grid-cols-3 gap-3">
                <div class="rounded-2xl bg-pale/60 p-4"><p class="text-xs font-semibold text-ink/45">Active</p><p class="mt-1 text-2xl font-bold text-emerald-600">{{ headcount.active }}</p></div>
                <div class="rounded-2xl bg-pale/60 p-4"><p class="text-xs font-semibold text-ink/45">Exited</p><p class="mt-1 text-2xl font-bold text-slate-500">{{ headcount.exited }}</p></div>
                <div class="rounded-2xl bg-pale/60 p-4"><p class="text-xs font-semibold text-ink/45">Total</p><p class="mt-1 text-2xl font-bold text-ink">{{ headcount.total }}</p></div>
            </div>

            <div v-if="headcount.by_department.length" class="mt-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink/40">By department</p>
                <div class="mt-2 space-y-2">
                    <div v-for="dept in headcount.by_department" :key="dept.department" class="flex items-center gap-3">
                        <span class="w-28 shrink-0 truncate text-sm font-medium text-ink/70">{{ dept.department }}</span>
                        <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-ink/5">
                            <div class="h-full rounded-full bg-base-action" :style="{ width: `${barWidth(dept.count, maxDept)}%` }" />
                        </div>
                        <span class="w-6 text-right text-sm font-semibold text-ink">{{ dept.count }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink/40">Hires &amp; exits (6 months)</p>
                <div class="mt-3 flex items-end gap-2">
                    <div v-for="m in headcount.trend" :key="m.label" class="flex flex-1 flex-col items-center gap-1">
                        <div class="flex h-24 w-full items-end justify-center gap-0.5">
                            <div class="w-2.5 rounded-t bg-emerald-400" :style="{ height: `${barWidth(m.hires, maxTrend) * 0.9 + 4}%` }" :title="`${m.hires} hires`" />
                            <div class="w-2.5 rounded-t bg-slate-300" :style="{ height: `${barWidth(m.exits, maxTrend) * 0.9 + 4}%` }" :title="`${m.exits} exits`" />
                        </div>
                        <span class="text-[10px] font-semibold text-ink/40">{{ m.label.split(' ')[0] }}</span>
                    </div>
                </div>
                <div class="mt-2 flex gap-4 text-[11px] font-medium text-ink/50">
                    <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-sm bg-emerald-400" />Hires</span>
                    <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-sm bg-slate-300" />Exits</span>
                </div>
            </div>
        </section>

        <!-- Leave utilisation -->
        <section class="mt-6 rounded-[1.5rem] bg-white p-6 shadow-premium ring-1 ring-ink/[0.06]">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Leave utilisation · {{ leave.year }}</h2>
                <a :href="route('admin.hr.reports.export', 'leave')" class="inline-flex items-center gap-1.5 text-xs font-semibold text-base-action hover:text-base-hover"><i class="ti ti-download" aria-hidden="true" />CSV</a>
            </div>
            <p class="mt-2 text-sm font-medium text-ink/55">{{ leave.total_used }} of {{ leave.total_allowance }} allocated days taken across the team.</p>
            <div v-if="leave.rows.length" class="mt-4 space-y-2.5">
                <div v-for="row in leave.rows" :key="row.user_id" class="flex items-center gap-3">
                    <span class="w-32 shrink-0 truncate text-sm font-medium text-ink/70">{{ row.name }}</span>
                    <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-ink/5">
                        <div class="h-full rounded-full" :class="row.utilisation >= 100 ? 'bg-coral' : 'bg-base-action'" :style="{ width: `${Math.min(row.utilisation, 100)}%` }" />
                    </div>
                    <span class="w-24 text-right text-xs font-semibold text-ink/60">{{ row.used }}/{{ row.allowance }}d</span>
                </div>
            </div>
            <p v-else class="mt-4 text-sm font-medium text-ink/45">No managed staff yet.</p>
        </section>

        <!-- Performance coverage -->
        <section class="mt-6 rounded-[1.5rem] bg-white p-6 shadow-premium ring-1 ring-ink/[0.06]">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Performance coverage</h2>
                <a :href="route('admin.hr.reports.export', 'performance')" class="inline-flex items-center gap-1.5 text-xs font-semibold text-base-action hover:text-base-hover"><i class="ti ti-download" aria-hidden="true" />CSV</a>
            </div>
            <p class="mt-2 text-sm font-medium text-ink/55">{{ performance.covered }} of {{ performance.total }} active staff have a check-in in the last {{ performance.window_days }} days.</p>
            <div class="mt-4 flex items-center gap-4">
                <div class="relative flex h-24 w-24 items-center justify-center">
                    <svg viewBox="0 0 36 36" class="h-24 w-24 -rotate-90">
                        <circle cx="18" cy="18" r="15.9" fill="none" stroke="#E3ECFC" stroke-width="3.5" />
                        <circle cx="18" cy="18" r="15.9" fill="none" stroke="#1A4FB5" stroke-width="3.5" stroke-linecap="round" :stroke-dasharray="`${performance.coverage_pct} 100`" />
                    </svg>
                    <span class="absolute text-lg font-bold text-ink">{{ performance.coverage_pct }}%</span>
                </div>
                <div class="text-sm font-medium text-ink/60">
                    <p><span class="font-semibold text-emerald-600">{{ performance.covered }}</span> with a recent note</p>
                    <p class="mt-1"><span class="font-semibold text-amber-600">{{ performance.uncovered }}</span> without one</p>
                </div>
            </div>
        </section>

        <!-- Payroll cost (gated) -->
        <section v-if="can.payroll && payroll" class="mt-6 rounded-[1.5rem] bg-white p-6 shadow-premium ring-1 ring-ink/[0.06]">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Payroll cost</h2>
                <a :href="route('admin.hr.reports.export', 'payroll')" class="inline-flex items-center gap-1.5 text-xs font-semibold text-base-action hover:text-base-hover"><i class="ti ti-download" aria-hidden="true" />CSV</a>
            </div>
            <p class="mt-2 text-sm font-medium text-ink/55">{{ payroll.currency }} {{ formatNumber(payroll.total_last_6_months) }} net over the last 6 months (aggregate).</p>
            <div class="mt-4 flex items-end gap-2">
                <div v-for="m in payroll.trend" :key="m.label" class="flex flex-1 flex-col items-center gap-1">
                    <div class="flex h-28 w-full items-end justify-center">
                        <div class="w-6 rounded-t bg-base-action" :style="{ height: `${barWidth(m.total, maxPayroll) * 0.92 + 4}%` }" :title="`${payroll.currency} ${formatNumber(m.total)}`" />
                    </div>
                    <span class="text-[10px] font-semibold text-ink/40">{{ m.label.split(' ')[0] }}</span>
                </div>
            </div>
        </section>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    headcount: { type: Object, required: true },
    leave: { type: Object, required: true },
    performance: { type: Object, required: true },
    payroll: { type: Object, default: null },
    can: { type: Object, default: () => ({}) },
});

const barWidth = (value, max) => (max > 0 ? Math.round((value / max) * 100) : 0);
const maxDept = computed(() => Math.max(1, ...props.headcount.by_department.map((d) => d.count)));
const maxTrend = computed(() => Math.max(1, ...props.headcount.trend.flatMap((m) => [m.hires, m.exits])));
const maxPayroll = computed(() => Math.max(1, ...(props.payroll?.trend?.map((m) => m.total) || [1])));
const formatNumber = (n) => Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: 0 });
</script>
