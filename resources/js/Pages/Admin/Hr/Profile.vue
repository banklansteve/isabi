<template>
    <Head :title="profile.name" />

    <AdminChrome
        :title="profile.name"
        :eyebrow="profileEyebrow"
        :back-href="route('admin.hr.index')"
        back-label="Back to directory"
    />
        <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-3.5">
                <Link
                    :href="route('admin.hr.index')"
                    prefetch
                    cache-for="5m"
                    :show-progress="false"
                    class="tap-target mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-ink/50 shadow-premium ring-1 ring-ink/[0.06] transition-colors hover:bg-pale hover:text-ink"
                    aria-label="Back to directory"
                >
                    <i class="ti ti-arrow-left text-lg" aria-hidden="true" />
                </Link>
                <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-tint text-sm font-bold text-deep">
                    <img v-if="profile.avatar_url" :src="profile.avatar_url" :alt="profile.name" class="h-full w-full object-cover" />
                    <template v-else>{{ profile.initials }}</template>
                </span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span :class="[pillBase, statusMeta.class]">
                            <span class="h-1.5 w-1.5 rounded-full" :class="statusMeta.dot" />
                            {{ statusMeta.label }}
                        </span>
                    </div>
                    <Link
                        :href="route('admin.staff.show', profile.id)"
                        class="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold text-base-action transition-colors hover:text-base-hover"
                    >
                        <i class="ti ti-shield-lock" aria-hidden="true" />
                        View platform access record
                    </Link>
                </div>
            </div>
            <div v-if="profile.has_profile" class="flex flex-wrap gap-2">
                <FormButton v-if="can.manage" variant="secondary" icon-left="ti ti-pencil" label="Edit employment" @click="openProfileModal" />
                <FormButton v-if="can.manage && profile.employment_status !== 'exited'" variant="secondary" icon-left="ti ti-door-exit" label="Mark as exited" @click="exitOpen = true" />
                <FormButton v-if="can.discipline_manage" variant="primary" icon-left="ti ti-clipboard-plus" label="Open a case" @click="openCreateCase" />
            </div>
        </div>

        <div
            v-if="profile.employment_status === 'exited'"
            class="mb-5 flex items-start gap-2.5 rounded-xl bg-slate-100 px-4 py-3 text-sm font-medium text-slate-600"
        >
            <i class="ti ti-archive mt-0.5" aria-hidden="true" />
            <span>
                Former staff — exited {{ profile.exit_date_label || 'recently' }}. Historical records are preserved for compliance.
                <span v-if="profile.exit_reason" class="block text-slate-500">Reason: {{ profile.exit_reason }}</span>
            </span>
        </div>

        <nav class="mb-6 no-scrollbar flex gap-1 overflow-x-auto rounded-full bg-pale p-1" aria-label="Profile sections">
            <button
                v-for="tab in visibleTabs"
                :key="tab.key"
                type="button"
                class="tap-target inline-flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-1.5 text-[13px] font-semibold transition-all duration-150"
                :class="activeTab === tab.key ? 'bg-white text-ink shadow-sm' : 'text-ink/45 hover:text-ink'"
                @click="setTab(tab.key)"
            >
                {{ tab.label }}
            </button>
        </nav>

        <div>
            <!-- OVERVIEW -->
            <section v-if="activeTab === 'overview'" class="space-y-6">
                <div v-if="!profile.has_profile" class="rounded-[1.5rem] border border-dashed border-ink/15 bg-white px-6 py-12 text-center shadow-premium">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-pale text-2xl text-ink/35">
                        <i class="ti ti-id-badge-2" aria-hidden="true" />
                    </span>
                    <p class="mt-4 text-base font-semibold text-ink">No HR profile yet</p>
                    <p class="mx-auto mt-1 max-w-sm text-sm font-medium text-ink/45">
                        Add employment details to start managing {{ profile.name }} as a staff member.
                    </p>
                    <FormButton v-if="can.manage" class="mx-auto mt-6" variant="primary" icon-left="ti ti-plus" label="Add HR profile" @click="openProfileModal" />
                </div>

                <template v-else>
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="rounded-[1.5rem] bg-white p-6 shadow-premium ring-1 ring-ink/[0.06]">
                            <div class="flex items-center justify-between">
                                <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Employment</h2>
                                <button v-if="can.manage" type="button" class="text-xs font-semibold text-base-action hover:text-base-hover" @click="openProfileModal">Edit</button>
                            </div>
                            <dl class="mt-4 space-y-3 text-sm">
                                <div class="flex justify-between gap-4"><dt class="text-ink/45">Position</dt><dd class="text-right font-semibold text-ink">{{ profile.position || '—' }}</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-ink/45">Department</dt><dd class="text-right font-semibold text-ink">{{ profile.department || '—' }}</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-ink/45">Employment type</dt><dd class="text-right font-semibold text-ink">{{ profile.employment_type || '—' }}</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-ink/45">Employment status</dt><dd class="text-right font-semibold text-ink">{{ statusMeta.label }}</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-ink/45">Start date</dt><dd class="text-right font-semibold text-ink">{{ profile.start_date_label || '—' }}</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-ink/45">Platform role</dt><dd class="text-right font-semibold text-ink">{{ profile.role_label }}</dd></div>
                            </dl>
                        </div>

                        <div class="rounded-[1.5rem] bg-white p-6 shadow-premium ring-1 ring-ink/[0.06]">
                            <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Personal &amp; emergency</h2>
                            <dl class="mt-4 space-y-3 text-sm">
                                <div class="flex justify-between gap-4"><dt class="text-ink/45">Personal email</dt><dd class="text-right font-semibold text-ink">{{ profile.personal_email || '—' }}</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-ink/45">Personal phone</dt><dd class="text-right font-semibold text-ink">{{ profile.personal_phone || '—' }}</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-ink/45">Emergency contact</dt><dd class="text-right font-semibold text-ink">{{ profile.emergency_contact_name || '—' }}</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-ink/45">Emergency phone</dt><dd class="text-right font-semibold text-ink">{{ profile.emergency_contact_phone || '—' }}</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-ink/45">Relationship</dt><dd class="text-right font-semibold text-ink">{{ profile.emergency_contact_relationship || '—' }}</dd></div>
                            </dl>
                        </div>
                    </div>

                    <!-- Checklists -->
                    <div class="rounded-[1.5rem] bg-white p-6 shadow-premium ring-1 ring-ink/[0.06]">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Onboarding &amp; offboarding</h2>
                            <div v-if="can.manage" class="flex items-center gap-2">
                                <select v-model="checklistTemplateId" class="rounded-xl border border-ink/10 bg-white px-3 py-2 text-xs font-medium text-ink outline-none focus:border-base">
                                    <option value="">Start a checklist…</option>
                                    <option v-for="tpl in checklistTemplates" :key="tpl.id" :value="tpl.id">{{ tpl.name }}</option>
                                </select>
                                <button type="button" class="tap-target rounded-xl bg-tint px-3 py-2 text-xs font-semibold text-deep disabled:opacity-40" :disabled="!checklistTemplateId || busyKey === 'checklist'" @click="attachChecklist">{{ busyKey === 'checklist' ? 'Adding…' : 'Add' }}</button>
                            </div>
                        </div>

                        <p v-if="checklists.length === 0" class="mt-4 text-sm font-medium text-ink/45">No checklists yet.</p>
                        <div v-else class="mt-4 space-y-5">
                            <div v-for="list in checklists" :key="list.id" class="rounded-2xl bg-pale/60 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-ink">{{ list.name }}</p>
                                    <span class="text-xs font-semibold" :class="list.progress === 100 ? 'text-emerald-600' : 'text-ink/45'">{{ list.progress }}%</span>
                                </div>
                                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-ink/10">
                                    <div class="h-full rounded-full bg-base-action transition-all" :style="{ width: `${list.progress}%` }" />
                                </div>
                                <ul class="mt-3 space-y-1.5">
                                    <li v-for="item in list.items" :key="item.id">
                                        <button
                                            type="button"
                                            class="flex w-full items-start gap-2.5 rounded-lg px-2 py-1.5 text-left text-sm transition-colors hover:bg-white disabled:cursor-default"
                                            :disabled="!can.manage"
                                            @click="toggleChecklistItem(item)"
                                        >
                                            <span class="mt-0.5 flex h-4.5 w-4.5 shrink-0 items-center justify-center rounded border" :class="item.is_done ? 'border-base bg-base' : 'border-ink/20 bg-white'">
                                                <i v-if="item.is_done" class="ti ti-check text-[11px] text-white" aria-hidden="true" />
                                            </span>
                                            <span :class="item.is_done ? 'text-ink/40 line-through' : 'text-ink/75'">{{ item.label }}</span>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div v-if="can.manage" class="flex flex-wrap gap-2">
                        <FormButton v-if="profile.employment_status !== 'exited'" variant="secondary" icon-left="ti ti-door-exit" label="Mark as exited" @click="exitOpen = true" />
                        <FormButton v-else variant="secondary" icon-left="ti ti-arrow-back-up" label="Reactivate" :loading="reactivateBusy" loading-label="Saving…" @click="reactivate" />
                    </div>
                </template>
            </section>

            <!-- LEAVE -->
            <section v-else-if="activeTab === 'leave'" class="space-y-6">
                <div class="grid gap-3 sm:grid-cols-3">
                    <div v-for="bal in leave.balances" :key="bal.leave_type_id" class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.06]">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full" :class="leaveDot(bal.color)" />
                            <p class="text-sm font-semibold text-ink">{{ bal.name }}</p>
                        </div>
                        <p class="mt-2 text-2xl font-bold tracking-tight" :class="bal.remaining_days < 0 ? 'text-coral-deep' : 'text-ink'">
                            {{ bal.remaining_days }}<span class="text-sm font-semibold text-ink/40"> / {{ bal.allowance_days }}d</span>
                        </p>
                        <p class="mt-0.5 text-xs font-medium text-ink/45">
                            {{ bal.used_days }} used<span v-if="bal.pending_days > 0"> · {{ bal.pending_days }} pending</span>
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <FormButton v-if="can.manage || can.leave" variant="primary" icon-left="ti ti-plus" label="Log leave" @click="openLeaveModal" />
                    <FormButton v-if="can.leave" variant="secondary" icon-left="ti ti-adjustments" label="Adjust balance" @click="openAllocateModal" />
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.06]">
                    <div v-if="leave.requests.length === 0" class="px-6 py-14 text-center">
                        <p class="text-sm font-semibold text-ink">No leave yet</p>
                        <p class="mt-1 text-sm font-medium text-ink/45">Book time off here or from the Leave inbox — it will show on the calendar.</p>
                        <FormButton v-if="can.manage || can.leave" class="mx-auto mt-5" variant="primary" icon-left="ti ti-plus" label="Book leave" @click="openLeaveModal" />
                    </div>
                    <ul v-else class="divide-y divide-ink/10">
                        <li v-for="req in leave.requests" :key="req.id" class="px-5 py-4 sm:px-6">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full" :class="leaveDot(req.leave_color)" />
                                        <p class="text-sm font-semibold text-ink">{{ req.leave_type }}</p>
                                        <span :class="[pillBase, leaveStatus(req.status).class]">{{ leaveStatus(req.status).label }}</span>
                                    </div>
                                    <p class="mt-1 text-xs font-medium text-ink/50">
                                        {{ req.start_label }} – {{ req.end_label }} · {{ req.days }} day{{ req.days === 1 ? '' : 's' }}
                                    </p>
                                    <p v-if="req.note" class="mt-1 text-xs font-medium text-ink/45">{{ req.note }}</p>
                                    <p v-if="req.decision_comment" class="mt-1 text-xs font-medium text-ink/45 italic">“{{ req.decision_comment }}”</p>
                                </div>
                                <div v-if="can.leave" class="flex shrink-0 gap-1.5">
                                    <template v-if="req.status === 'pending'">
                                        <button type="button" class="tap-target rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 disabled:opacity-50" :disabled="decisionForm.processing" @click="openDecision(req, 'approve')">Approve</button>
                                        <button type="button" class="tap-target rounded-lg bg-coral-tint px-3 py-1.5 text-xs font-semibold text-coral-deep hover:bg-coral-tint/70 disabled:opacity-50" :disabled="decisionForm.processing" @click="openDecision(req, 'reject')">Reject</button>
                                    </template>
                                    <button v-else-if="req.status === 'approved'" type="button" class="tap-target rounded-lg bg-pale px-3 py-1.5 text-xs font-semibold text-ink/60 hover:bg-tint disabled:opacity-50" :disabled="busyKey === `cancel-${req.id}`" @click="cancelLeave(req)">{{ busyKey === `cancel-${req.id}` ? 'Working…' : 'Cancel' }}</button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- PAYROLL -->
            <section v-else-if="activeTab === 'payroll' && can.payroll_view" class="space-y-6">
                <div class="rounded-[1.5rem] bg-white p-6 shadow-premium ring-1 ring-ink/[0.06]">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Compensation</h2>
                        <div v-if="can.payroll_manage" class="flex flex-wrap gap-2">
                            <FormButton variant="secondary" icon-left="ti ti-plus" label="Add allowance" @click="openAllowanceDrawer()" />
                            <FormButton variant="primary" :label="payroll?.compensation ? 'Adjust pay' : 'Set salary'" @click="openCompModal" />
                        </div>
                    </div>
                    <div v-if="payroll?.compensation" class="mt-4">
                        <p class="text-3xl font-bold tracking-tight text-ink">{{ money(payroll.compensation.gross_total, payroll.compensation.currency) }}<span class="text-sm font-semibold text-ink/40"> / {{ payroll.compensation.pay_frequency }}</span></p>
                        <p v-if="payroll.compensation.effective_from_label" class="mt-1 text-xs font-medium text-ink/45">Effective {{ payroll.compensation.effective_from_label }}</p>
                        <dl class="mt-4 space-y-2 text-sm">
                            <div class="flex justify-between gap-4"><dt class="text-ink/45">Base</dt><dd class="font-semibold text-ink">{{ money(payroll.compensation.base_salary, payroll.compensation.currency) }}</dd></div>
                            <div v-for="a in payroll.compensation.allowances" :key="a.id" class="flex items-center justify-between gap-4">
                                <dt class="text-ink/45">{{ a.label }}</dt>
                                <dd class="flex items-center gap-2 font-semibold text-ink">
                                    {{ money(a.amount, payroll.compensation.currency) }}
                                    <button v-if="can.payroll_manage" type="button" class="text-xs font-semibold text-base-action hover:text-base-hover" @click="openAllowanceDrawer(a)">Edit</button>
                                    <button v-if="can.payroll_manage" type="button" class="text-xs font-semibold text-ink/40 hover:text-coral-deep" @click="openEndAllowance(a)">End</button>
                                </dd>
                            </div>
                        </dl>
                    </div>
                    <div v-else class="mt-6 rounded-2xl border border-dashed border-ink/15 px-6 py-10 text-center">
                        <p class="text-sm font-semibold text-ink">No salary on file</p>
                        <p class="mt-1 text-sm font-medium text-ink/45">Set base pay, currency, and cycle. Each change is dated and kept as history.</p>
                    </div>
                </div>

                <div v-if="compensationHistory.length" class="rounded-[1.5rem] bg-white p-6 shadow-premium ring-1 ring-ink/[0.06]">
                    <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Pay history</h2>
                    <ol class="mt-4 space-y-3">
                        <li v-for="row in compensationHistory" :key="row.id" class="rounded-2xl bg-pale/60 px-4 py-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-ink">{{ money(row.gross_total, row.currency) }} <span class="font-medium text-ink/45">/ {{ row.pay_frequency }}</span></p>
                                <span class="text-xs font-medium text-ink/45">{{ row.effective_from_label || row.updated_at_label }}</span>
                            </div>
                            <p class="mt-1 text-xs font-medium text-ink/50">
                                Base {{ money(row.base_salary, row.currency) }}
                                <span v-if="row.allowances.length"> · {{ row.allowances.map((a) => a.label).join(', ') }}</span>
                            </p>
                            <p v-if="row.reason" class="mt-1 text-xs font-medium text-ink/45">{{ row.reason }}</p>
                        </li>
                    </ol>
                </div>

                <div class="rounded-[1.5rem] bg-white p-6 shadow-premium ring-1 ring-ink/[0.06]">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Payslips</h2>
                        <FormButton v-if="can.payroll_manage" variant="secondary" icon-left="ti ti-plus" label="Generate" @click="openPayslipModal" />
                    </div>
                    <div v-if="!payroll?.payslips?.length" class="mt-6 rounded-2xl border border-dashed border-ink/15 px-6 py-10 text-center">
                        <p class="text-sm font-semibold text-ink">No payslips yet</p>
                        <p class="mt-1 text-sm font-medium text-ink/45">Generate what is owed for a period. This does not move money.</p>
                    </div>
                    <ul v-else class="mt-4 divide-y divide-ink/10">
                        <li v-for="slip in payroll.payslips" :key="slip.id" class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <button type="button" class="min-w-0 text-left" @click="openPayslipView(slip)">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold text-ink">{{ slip.period_label }}</p>
                                    <span :class="[pillBase, payslipStatus(slip.status).class]">{{ payslipStatus(slip.status).label }}</span>
                                </div>
                                <p class="mt-0.5 text-xs font-medium text-ink/45">Net {{ money(slip.net_pay, slip.currency) }}</p>
                            </button>
                            <div class="flex shrink-0 items-center gap-1.5">
                                <a :href="route('admin.hr.payslips.pdf', slip.id)" class="tap-target rounded-lg bg-pale px-3 py-1.5 text-xs font-semibold text-ink/70 hover:bg-tint">PDF</a>
                                <button v-if="can.payroll_manage && slip.status === 'issued'" type="button" class="tap-target rounded-lg bg-tint px-3 py-1.5 text-xs font-semibold text-deep disabled:opacity-50" :disabled="busyKey === `reissue-${slip.id}`" @click="openReissue(slip)">{{ busyKey === `reissue-${slip.id}` ? 'Working…' : 'Reissue' }}</button>
                                <button v-if="can.payroll_manage && slip.status === 'draft'" type="button" class="tap-target rounded-lg bg-tint px-3 py-1.5 text-xs font-semibold text-deep disabled:opacity-50" :disabled="busyKey === `issue-${slip.id}`" @click="setPayslipStatus(slip, 'issued')">{{ busyKey === `issue-${slip.id}` ? 'Working…' : 'Issue' }}</button>
                                <button v-if="can.payroll_manage && slip.status === 'issued'" type="button" class="tap-target rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 disabled:opacity-50" :disabled="busyKey === `paid-${slip.id}`" @click="setPayslipStatus(slip, 'paid')">{{ busyKey === `paid-${slip.id}` ? 'Working…' : 'Mark paid' }}</button>
                                <button v-if="can.payroll_manage && slip.status === 'draft'" type="button" class="tap-target rounded-lg px-2 py-1.5 text-xs font-semibold text-ink/40 hover:text-coral-deep" aria-label="Delete draft" @click="deletePayslip(slip)"><i class="ti ti-trash" aria-hidden="true" /></button>
                            </div>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- CASES -->
            <section v-else-if="activeTab === 'discipline'" class="space-y-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Cases</h2>
                        <p class="mt-1 text-sm font-medium text-ink/50">
                            {{ caseHistoryHint }}
                        </p>
                    </div>
                    <FormButton v-if="can.discipline_manage" variant="primary" icon-left="ti ti-plus" label="Open a case" @click="openCreateCase" />
                </div>

                <div v-if="!(discipline?.cases || []).length" class="rounded-[1.5rem] border border-dashed border-ink/15 bg-white px-6 py-12 text-center shadow-premium">
                    <p class="text-base font-semibold text-ink">No cases on this file</p>
                    <p class="mx-auto mt-1 max-w-sm text-sm font-medium text-ink/45">A case is a permanent record. Open one only when an incident needs documenting.</p>
                    <FormButton v-if="can.discipline_manage" class="mx-auto mt-6" variant="primary" icon-left="ti ti-plus" label="Open a case" @click="openCreateCase" />
                </div>
                <ul v-else class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.06] divide-y divide-ink/10">
                    <li v-for="item in discipline.cases" :key="item.id">
                        <button type="button" class="flex w-full items-start gap-3.5 px-5 py-4 text-left hover:bg-pale/70" @click="openProfileCase(item)">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-semibold text-ink">{{ item.reference }}</p>
                                    <span :class="[pillBase, disciplineStatus(item.status).class]">{{ item.status_label }}</span>
                                    <span :class="[pillBase, disciplineSeverity(item.severity).class]">{{ item.severity_label }}</span>
                                </div>
                                <p class="mt-0.5 text-sm font-medium text-ink/55">{{ item.category_label }} · Incident {{ item.incident_label }}</p>
                                <p class="mt-1 text-xs font-medium text-ink/40">Updated {{ item.updated_label }}</p>
                            </div>
                            <i class="ti ti-chevron-right mt-2 shrink-0 text-ink/25" aria-hidden="true" />
                        </button>
                    </li>
                </ul>
            </section>

            <!-- PERFORMANCE -->
            <section v-else-if="activeTab === 'performance'" class="space-y-6">
                <div class="flex justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Performance notes</h2>
                    <FormButton v-if="can.manage" variant="secondary" icon-left="ti ti-plus" label="Add note" @click="openNoteModal" />
                </div>
                <div v-if="performance.notes.length === 0" class="rounded-[1.5rem] border border-dashed border-ink/15 bg-white px-6 py-12 text-center shadow-premium">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-pale text-2xl text-ink/35"><i class="ti ti-notes" aria-hidden="true" /></span>
                    <p class="mt-4 text-base font-semibold text-ink">No performance notes yet</p>
                    <p class="mx-auto mt-1 max-w-sm text-sm font-medium text-ink/45">Keep a light, ongoing record of check-ins and feedback.</p>
                    <FormButton v-if="can.manage" class="mx-auto mt-6" variant="primary" icon-left="ti ti-plus" label="Add the first note" @click="openNoteModal" />
                </div>
                <ol v-else class="relative space-y-4 border-l border-ink/10 pl-6">
                    <li v-for="note in performance.notes" :key="note.id" class="relative">
                        <span class="absolute -left-[1.65rem] top-1 flex h-3 w-3 items-center justify-center rounded-full bg-white ring-2 ring-base/40"><span class="h-1.5 w-1.5 rounded-full bg-base" /></span>
                        <div class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.06]">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold text-ink/45">{{ note.noted_on_label }}</span>
                                    <span v-if="ratingBadge(note.rating)" :class="[pillBase, ratingBadge(note.rating).class]">{{ ratingBadge(note.rating).label }}</span>
                                </div>
                                <button v-if="can.manage" type="button" class="text-ink/30 transition-colors hover:text-coral-deep" aria-label="Delete note" @click="deleteNote(note)"><i class="ti ti-trash text-sm" aria-hidden="true" /></button>
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm font-medium leading-relaxed text-ink/75">{{ note.body }}</p>
                            <p class="mt-2 text-xs font-medium text-ink/40">{{ note.author_name }}</p>
                        </div>
                    </li>
                </ol>
            </section>

            <!-- DOCUMENTS -->
            <section v-else-if="activeTab === 'documents'" class="space-y-6">
                <div v-if="expiringDocs.length" class="flex items-start gap-2.5 rounded-xl bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                    <i class="ti ti-alert-triangle mt-0.5" aria-hidden="true" />
                    <span>{{ expiringDocs.length }} document{{ expiringDocs.length === 1 ? '' : 's' }} expiring soon or expired — review renewals.</span>
                </div>
                <div class="flex justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-ink/40">Documents</h2>
                    <FormButton v-if="can.manage" variant="secondary" icon-left="ti ti-upload" label="Upload" @click="openDocModal" />
                </div>
                <div v-if="documents.length === 0" class="rounded-[1.5rem] border border-dashed border-ink/15 bg-white px-6 py-12 text-center shadow-premium">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-pale text-2xl text-ink/35"><i class="ti ti-files" aria-hidden="true" /></span>
                    <p class="mt-4 text-base font-semibold text-ink">No documents yet</p>
                    <p class="mx-auto mt-1 max-w-sm text-sm font-medium text-ink/45">Store contracts, offer letters, and IDs securely against this profile.</p>
                </div>
                <ul v-else class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.06] divide-y divide-ink/10">
                    <li v-for="doc in documents" :key="doc.id" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
                        <div class="min-w-0 flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-pale text-ink/45"><i class="ti ti-file-text text-lg" aria-hidden="true" /></span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-ink">{{ doc.title }}</p>
                                <p class="truncate text-xs font-medium text-ink/45">{{ docTypeLabel(doc.type) }}<span v-if="doc.expiry_label"> · expires {{ doc.expiry_label }}</span></p>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <span v-if="expiryBadge(doc.expiry_state)" :class="[pillBase, expiryBadge(doc.expiry_state).class]">{{ expiryBadge(doc.expiry_state).label }}</span>
                            <a :href="route('admin.hr.documents.download', doc.id)" class="tap-target rounded-lg bg-pale px-3 py-1.5 text-xs font-semibold text-ink/70 hover:bg-tint">Download</a>
                            <button v-if="can.manage" type="button" class="tap-target rounded-lg bg-tint px-3 py-1.5 text-xs font-semibold text-deep" @click="openDocEdit(doc)">Expiry</button>
                            <button v-if="can.manage" type="button" class="tap-target rounded-lg px-2 py-1.5 text-xs text-ink/40 hover:text-coral-deep" aria-label="Delete document" @click="deleteDoc(doc)"><i class="ti ti-trash" aria-hidden="true" /></button>
                        </div>
                    </li>
                </ul>
            </section>

        <!-- Profile modal -->
        <AppModal :show="profileOpen" title="Employment details" description="Who they are as an employee. Platform access is managed separately in Access & Roles." icon="ti ti-id-badge-2" size="lg" @close="profileOpen = false">
            <form class="space-y-4" @submit.prevent="submitProfile">
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormTextInput id="hp-position" v-model="profileForm.position" label="Position" icon="ti ti-briefcase" :error="profileForm.errors.position" />
                    <FormTextInput id="hp-department" v-model="profileForm.department" label="Department" icon="ti ti-building" :error="profileForm.errors.department" />
                    <FormSelect id="hp-type" v-model="profileForm.employment_type" label="Employment type" icon="ti ti-clock" :options="employmentTypes" :error="profileForm.errors.employment_type" />
                    <FormSelect id="hp-status" v-model="profileForm.employment_status" label="Employment status" icon="ti ti-status-change" :options="employmentStatuses" :error="profileForm.errors.employment_status" />
                    <FormTextInput id="hp-start" v-model="profileForm.start_date" type="date" :min="DATE_MIN" :max="DATE_MAX" label="Start date" icon="ti ti-calendar" :error="profileForm.errors.start_date" />
                    <FormTextInput id="hp-pemail" v-model="profileForm.personal_email" type="email" label="Personal email" icon="ti ti-mail" :error="profileForm.errors.personal_email" />
                    <FormTextInput id="hp-pphone" v-model="profileForm.personal_phone" label="Personal phone" icon="ti ti-phone" :error="profileForm.errors.personal_phone" />
                    <FormTextInput id="hp-dob" v-model="profileForm.date_of_birth" type="date" :min="DATE_MIN" :max="DATE_MAX" label="Date of birth" icon="ti ti-cake" :error="profileForm.errors.date_of_birth" />
                    <FormTextInput id="hp-addr" v-model="profileForm.home_address" label="Home address" icon="ti ti-map-pin" :error="profileForm.errors.home_address" />
                    <FormTextInput id="hp-ecn" v-model="profileForm.emergency_contact_name" label="Emergency contact" icon="ti ti-user" :error="profileForm.errors.emergency_contact_name" />
                    <FormTextInput id="hp-ecp" v-model="profileForm.emergency_contact_phone" label="Emergency phone" icon="ti ti-phone" :error="profileForm.errors.emergency_contact_phone" />
                    <FormTextInput id="hp-ecr" v-model="profileForm.emergency_contact_relationship" label="Relationship" icon="ti ti-heart" :error="profileForm.errors.emergency_contact_relationship" />
                </div>
                <div v-if="profileForm.employment_status === 'exited'" class="rounded-2xl bg-amber-50 px-4 py-3">
                    <p class="text-sm font-medium text-amber-900">History stays on file. Also revoke platform access in Access &amp; staff — the two systems are not synced.</p>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <FormTextInput id="hp-exit-date" v-model="profileForm.exit_date" type="date" :min="DATE_MIN" :max="DATE_MAX" label="Exit date" icon="ti ti-calendar" :error="profileForm.errors.exit_date" required />
                        <FormTextarea id="hp-exit-reason" v-model="profileForm.exit_reason" label="Reason" :error="profileForm.errors.exit_reason" required />
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <FormButton type="button" variant="secondary" label="Cancel" @click="profileOpen = false" />
                    <FormButton type="submit" variant="primary" label="Save" :loading="profileForm.processing" loading-label="Saving…" />
                </div>
            </form>
        </AppModal>

        <!-- Exit modal -->
        <AppModal :show="exitOpen" title="Mark as exited?" description="Historical records stay for compliance. Remember to also revoke platform access in Access & Roles — the two systems are not synced." icon="ti ti-door-exit" icon-tone="coral" @close="exitOpen = false">
            <form class="space-y-4" @submit.prevent="submitExit">
                <FormTextInput id="exit-date" v-model="exitForm.exit_date" type="date" :min="DATE_MIN" :max="DATE_MAX" label="Exit date" icon="ti ti-calendar" :error="exitForm.errors.exit_date" required />
                <FormTextarea id="exit-reason" v-model="exitForm.exit_reason" label="Reason" placeholder="Resignation, end of contract, etc." :error="exitForm.errors.exit_reason" required />
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <FormButton type="button" variant="secondary" label="Cancel" @click="exitOpen = false" />
                    <FormButton type="submit" variant="accent" label="Mark as exited" :loading="exitForm.processing" loading-label="Saving…" />
                </div>
            </form>
        </AppModal>

        <!-- Leave modal -->
        <AppModal :show="leaveOpen" title="Log leave" description="Enter a leave request. It starts as pending until approved." icon="ti ti-calendar-plus" @close="leaveOpen = false">
            <form class="space-y-4" @submit.prevent="submitLeave">
                <FormSelect id="lv-type" v-model="leaveForm.leave_type_id" label="Leave type" icon="ti ti-category" :options="leaveTypeOptions" :error="leaveForm.errors.leave_type_id" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormTextInput id="lv-start" v-model="leaveForm.start_date" type="date" :min="DATE_MIN" :max="DATE_MAX" label="Start date" icon="ti ti-calendar" :error="leaveForm.errors.start_date" required />
                    <FormTextInput id="lv-end" v-model="leaveForm.end_date" type="date" :min="DATE_MIN" :max="DATE_MAX" label="End date" icon="ti ti-calendar" :error="leaveForm.errors.end_date" required />
                </div>
                <p v-if="leaveDayCount" class="text-xs font-semibold text-ink/50">{{ leaveDayCount }} day{{ leaveDayCount === 1 ? '' : 's' }}</p>
                <FormTextarea id="lv-note" v-model="leaveForm.note" label="Note (optional)" :error="leaveForm.errors.note" />
                <label v-if="can.leave" class="flex items-center gap-2 text-sm font-medium text-ink/70">
                    <input v-model="leaveForm.approve_now" type="checkbox" class="rounded border-ink/30" />
                    Book as approved
                </label>
                <label v-if="can.leave && leaveForm.approve_now" class="flex items-center gap-2 text-sm font-medium text-ink/70">
                    <input v-model="leaveForm.override_negative" type="checkbox" class="rounded border-ink/30" />
                    Override if this exceeds the balance
                </label>
                <p v-if="leaveForm.errors.leave" class="text-sm font-medium text-coral-deep">{{ leaveForm.errors.leave }}</p>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <FormButton type="button" variant="secondary" label="Cancel" @click="leaveOpen = false" />
                    <FormButton type="submit" variant="primary" label="Submit" :loading="leaveForm.processing" loading-label="Submitting…" />
                </div>
            </form>
        </AppModal>

        <!-- Allocation modal -->
        <AppModal :show="allocateOpen" title="Adjust leave balance" description="This changes remaining days for the year. Used leave is not rewritten. A reason is required." icon="ti ti-adjustments" @close="allocateOpen = false">
            <form class="space-y-4" @submit.prevent="submitAllocation">
                <FormSelect id="al-type" v-model="allocateForm.leave_type_id" label="Leave type" icon="ti ti-category" :options="leaveTypeOptions" :error="allocateForm.errors.leave_type_id" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormTextInput id="al-year" v-model="allocateForm.year" type="number" label="Year" icon="ti ti-calendar" :error="allocateForm.errors.year" required />
                    <FormTextInput id="al-remain" v-model="allocateForm.remaining_days" type="number" label="Remaining days" icon="ti ti-hash" :error="allocateForm.errors.remaining_days" required />
                </div>
                <p v-if="allocatePreview" class="text-xs font-medium text-ink/50">{{ allocatePreview }}</p>
                <FormTextarea id="al-reason" v-model="allocateForm.reason" label="Reason" placeholder="Correction, carry-over, goodwill adjustment…" :error="allocateForm.errors.reason" required />
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <FormButton type="button" variant="secondary" label="Cancel" @click="allocateOpen = false" />
                    <FormButton type="submit" variant="primary" label="Save" :loading="allocateForm.processing" loading-label="Saving…" />
                </div>
            </form>
        </AppModal>

        <!-- Decision modal -->
        <AppModal :show="decisionOpen" :title="decisionMode === 'approve' ? 'Approve leave?' : 'Reject leave?'" :icon="decisionMode === 'approve' ? 'ti ti-check' : 'ti ti-x'" :icon-tone="decisionMode === 'approve' ? 'base' : 'coral'" @close="decisionOpen = false">
            <form class="space-y-4" @submit.prevent="submitDecision">
                <p v-if="decisionTarget" class="text-sm font-medium text-ink/60">
                    {{ decisionTarget.leave_type }} · {{ decisionTarget.start_label }} – {{ decisionTarget.end_label }} ({{ decisionTarget.days }}d)
                </p>
                <div v-if="decisionMode === 'approve' && decisionTarget?.would_go_negative" class="rounded-xl bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                    This will put the balance negative. Tick the override to approve anyway — it will be logged.
                    <label class="mt-2 flex items-center gap-2 text-xs font-semibold">
                        <input v-model="decisionForm.override_negative" type="checkbox" class="rounded border-ink/30" />
                        Override negative balance
                    </label>
                </div>
                <FormTextarea id="dec-comment" v-model="decisionForm.comment" label="Comment (optional)" :error="decisionForm.errors.comment" />
                <p v-if="decisionForm.errors.leave" class="text-sm font-medium text-coral-deep">{{ decisionForm.errors.leave }}</p>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <FormButton type="button" variant="secondary" label="Cancel" @click="decisionOpen = false" />
                    <FormButton type="submit" :variant="decisionMode === 'approve' ? 'primary' : 'accent'" :label="decisionMode === 'approve' ? 'Approve' : 'Reject'" :loading="decisionForm.processing" :loading-label="decisionMode === 'approve' ? 'Approving…' : 'Rejecting…'" />
                </div>
            </form>
        </AppModal>

        <!-- Compensation drawer -->
        <AdminDrawer :open="compOpen" title="Adjust pay" eyebrow="Creates a new dated record" @close="compOpen = false">
            <form class="space-y-4" @submit.prevent="submitComp">
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormTextInput id="cp-base" v-model="compForm.base_salary" type="number" label="Base salary" icon="ti ti-cash" :error="compForm.errors.base_salary" required />
                    <FormSelect id="cp-freq" v-model="compForm.pay_frequency" label="Pay cycle" icon="ti ti-repeat" :options="payFrequencies" :error="compForm.errors.pay_frequency" />
                    <FormTextInput id="cp-cur" v-model="compForm.currency" label="Currency" icon="ti ti-currency-naira" :error="compForm.errors.currency" />
                    <FormTextInput id="cp-eff" v-model="compForm.effective_from" type="date" :min="DATE_MIN" :max="DATE_MAX" label="Effective from" icon="ti ti-calendar" :error="compForm.errors.effective_from" required />
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-ink">Allowances</p>
                        <button type="button" class="text-xs font-semibold text-base-action hover:text-base-hover" @click="compForm.allowances.push({ label: '', amount: 0 })">+ Add allowance</button>
                    </div>
                    <div v-for="(a, i) in compForm.allowances" :key="i" class="mt-2 flex items-center gap-2">
                        <input v-model="a.label" placeholder="Housing, transport…" class="flex-1 rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm text-ink outline-none focus:border-base focus:ring-2 focus:ring-base/15" />
                        <input v-model="a.amount" type="number" placeholder="0" class="w-32 rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm text-ink outline-none focus:border-base focus:ring-2 focus:ring-base/15" />
                        <button type="button" class="text-ink/40 hover:text-coral-deep" aria-label="Remove" @click="compForm.allowances.splice(i, 1)"><i class="ti ti-x" aria-hidden="true" /></button>
                    </div>
                </div>
                <FormTextarea id="cp-reason" v-model="compForm.reason" label="Reason for change" placeholder="Annual review, promotion, correction…" :error="compForm.errors.reason" required />
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <FormButton type="button" variant="secondary" label="Cancel" @click="compOpen = false" />
                    <FormButton type="submit" variant="primary" label="Save & log" :loading="compForm.processing" loading-label="Saving…" />
                </div>
            </form>
        </AdminDrawer>

        <AdminDrawer :open="allowanceOpen" :title="allowanceDrawerTitle" eyebrow="New dated compensation record" @close="allowanceOpen = false">
            <form class="space-y-4" @submit.prevent="submitAllowanceChange">
                <template v-if="!endingAllowance">
                    <FormTextInput id="alw-label" v-model="allowanceForm.label" label="Allowance" icon="ti ti-home" placeholder="Housing, transport, data…" :error="allowanceForm.errors.label" required />
                    <FormTextInput id="alw-amount" v-model="allowanceForm.amount" type="number" label="Amount" icon="ti ti-cash" :error="allowanceForm.errors.amount" required />
                </template>
                <p v-else class="text-sm font-medium text-ink/60">This ends {{ endingAllowance.label }} on the next dated package. Earlier records stay in history.</p>
                <FormTextInput id="alw-eff" v-model="allowanceForm.effective_from" type="date" :min="DATE_MIN" :max="DATE_MAX" label="Effective from" icon="ti ti-calendar" :error="allowanceForm.errors.effective_from" required />
                <FormTextarea id="alw-reason" v-model="allowanceForm.reason" label="Reason" :error="allowanceForm.errors.reason" required />
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <FormButton type="button" variant="secondary" label="Cancel" @click="allowanceOpen = false" />
                    <FormButton type="submit" variant="primary" :label="allowanceSubmitLabel" :loading="compForm.processing" loading-label="Saving…" />
                </div>
            </form>
        </AdminDrawer>

        <AdminDrawer :open="!!viewingPayslip" :title="viewingPayslip?.period_label || 'Payslip'" eyebrow="Record only — no payout" @close="viewingPayslip = null">
            <div v-if="viewingPayslip" class="space-y-4">
                <div class="flex items-center justify-between">
                    <span :class="[pillBase, payslipStatus(viewingPayslip.status).class]">{{ payslipStatus(viewingPayslip.status).label }}</span>
                    <p class="text-xs font-medium text-ink/45">{{ viewingPayslip.period_range_label }}</p>
                </div>
                <p class="text-3xl font-bold tracking-tight text-ink">{{ money(viewingPayslip.net_pay, viewingPayslip.currency) }}</p>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-ink/45">Base</dt><dd class="font-semibold text-ink">{{ money(viewingPayslip.base_pay, viewingPayslip.currency) }}</dd></div>
                    <div v-for="item in viewingPayslip.allowance_items" :key="`a-${item.label}`" class="flex justify-between gap-4">
                        <dt class="text-ink/45">{{ item.label }}</dt>
                        <dd class="font-semibold text-ink">{{ money(item.amount, viewingPayslip.currency) }}</dd>
                    </div>
                    <div v-for="item in viewingPayslip.deduction_items" :key="`d-${item.label}`" class="flex justify-between gap-4">
                        <dt class="text-ink/45">{{ item.label }}</dt>
                        <dd class="font-semibold text-coral-deep">− {{ money(item.amount, viewingPayslip.currency) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-t border-ink/10 pt-2"><dt class="text-ink/45">Gross</dt><dd class="font-semibold text-ink">{{ money(viewingPayslip.gross_pay, viewingPayslip.currency) }}</dd></div>
                </dl>
                <p v-if="viewingPayslip.notes" class="text-sm font-medium text-ink/55">{{ viewingPayslip.notes }}</p>
                <div class="flex flex-wrap gap-2">
                    <a :href="route('admin.hr.payslips.pdf', viewingPayslip.id)" class="inline-flex items-center gap-1.5 text-sm font-semibold text-base-action hover:text-base-hover">Download PDF</a>
                    <FormButton v-if="can.payroll_manage && viewingPayslip.status === 'issued'" variant="secondary" label="Reissue" @click="openReissue(viewingPayslip)" />
                    <FormButton v-if="can.payroll_manage && viewingPayslip.status === 'draft'" variant="primary" label="Issue" :loading="busyKey === `issue-${viewingPayslip.id}`" loading-label="Working…" @click="setPayslipStatus(viewingPayslip, 'issued')" />
                    <FormButton v-if="can.payroll_manage && viewingPayslip.status === 'issued'" variant="secondary" label="Mark paid" :loading="busyKey === `paid-${viewingPayslip.id}`" loading-label="Working…" @click="setPayslipStatus(viewingPayslip, 'paid')" />
                </div>
            </div>
        </AdminDrawer>

        <!-- Payslip modal -->
        <AppModal :show="payslipOpen" title="Generate payslip" description="Records what is owed for a pay period. This does not move money." icon="ti ti-receipt" size="lg" @close="payslipOpen = false">
            <form class="space-y-4" @submit.prevent="submitPayslip">
                <FormTextInput id="ps-label" v-model="payslipForm.period_label" label="Period label" icon="ti ti-tag" placeholder="August 2026" :error="payslipForm.errors.period_label" required />
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormTextInput id="ps-start" v-model="payslipForm.period_start" type="date" :min="DATE_MIN" :max="DATE_MAX" label="Period start" icon="ti ti-calendar" :error="payslipForm.errors.period_start" required />
                    <FormTextInput id="ps-end" v-model="payslipForm.period_end" type="date" :min="DATE_MIN" :max="DATE_MAX" label="Period end" icon="ti ti-calendar" :error="payslipForm.errors.period_end" required />
                </div>
                <FormTextInput id="ps-base" v-model="payslipForm.base_pay" type="number" label="Base pay" icon="ti ti-cash" :error="payslipForm.errors.base_pay" required />
                <div>
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-ink">Allowances</p>
                        <button type="button" class="text-xs font-semibold text-base-action hover:text-base-hover" @click="payslipForm.allowances.push({ label: '', amount: 0 })">+ Add</button>
                    </div>
                    <div v-for="(a, i) in payslipForm.allowances" :key="`pa-${i}`" class="mt-2 flex items-center gap-2">
                        <input v-model="a.label" placeholder="Label" class="flex-1 rounded-xl border border-ink/10 bg-white px-3 py-2 text-sm outline-none focus:border-base" />
                        <input v-model="a.amount" type="number" placeholder="0" class="w-28 rounded-xl border border-ink/10 bg-white px-3 py-2 text-sm outline-none focus:border-base" />
                        <button type="button" class="text-ink/40 hover:text-coral-deep" aria-label="Remove" @click="payslipForm.allowances.splice(i, 1)"><i class="ti ti-x" aria-hidden="true" /></button>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-ink">Deductions</p>
                        <button type="button" class="text-xs font-semibold text-base-action hover:text-base-hover" @click="payslipForm.deductions.push({ label: '', amount: 0 })">+ Add</button>
                    </div>
                    <div v-for="(d, i) in payslipForm.deductions" :key="`pd-${i}`" class="mt-2 flex items-center gap-2">
                        <input v-model="d.label" placeholder="Label" class="flex-1 rounded-xl border border-ink/10 bg-white px-3 py-2 text-sm outline-none focus:border-base" />
                        <input v-model="d.amount" type="number" placeholder="0" class="w-28 rounded-xl border border-ink/10 bg-white px-3 py-2 text-sm outline-none focus:border-base" />
                        <button type="button" class="text-ink/40 hover:text-coral-deep" aria-label="Remove" @click="payslipForm.deductions.splice(i, 1)"><i class="ti ti-x" aria-hidden="true" /></button>
                    </div>
                </div>
                <div class="rounded-xl bg-pale px-4 py-3 text-sm font-semibold text-ink">
                    Net: {{ money(payslipNet, payroll?.compensation?.currency || 'NGN') }}
                </div>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <FormButton type="button" variant="secondary" label="Cancel" @click="payslipOpen = false" />
                    <FormButton type="submit" variant="primary" label="Generate" :loading="payslipForm.processing" loading-label="Generating…" />
                </div>
            </form>
        </AppModal>

        <!-- Note modal -->
        <AppModal :show="noteOpen" title="Add performance note" icon="ti ti-notes" @close="noteOpen = false">
            <form class="space-y-4" @submit.prevent="submitNote">
                <FormTextInput id="nt-date" v-model="noteForm.noted_on" type="date" :min="DATE_MIN" :max="DATE_MAX" label="Date" icon="ti ti-calendar" :error="noteForm.errors.noted_on" required />
                <FormSelect id="nt-rating" v-model="noteForm.rating" label="Rating (optional)" icon="ti ti-star" :options="ratingOptions" placeholder="No rating" :error="noteForm.errors.rating" />
                <FormTextarea id="nt-body" v-model="noteForm.body" label="Note" rows="4" placeholder="Q2 check-in — strong response times, needs improvement on documentation." :error="noteForm.errors.body" required />
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <FormButton type="button" variant="secondary" label="Cancel" @click="noteOpen = false" />
                    <FormButton type="submit" variant="primary" label="Add note" :loading="noteForm.processing" loading-label="Saving…" />
                </div>
            </form>
        </AppModal>

        <!-- Document modal -->
        <AppModal :show="docOpen" :title="editingDoc ? 'Update document' : 'Upload document'" icon="ti ti-upload" @close="docOpen = false">
            <form class="space-y-4" @submit.prevent="submitDoc">
                <FormTextInput id="dc-title" v-model="docForm.title" label="Title" icon="ti ti-file" placeholder="Signed contract 2026" :error="docForm.errors.title" required />
                <FormSelect id="dc-type" v-model="docForm.type" label="Type" icon="ti ti-category" :options="documentTypes" value-key="value" label-key="label" :error="docForm.errors.type" />
                <FormTextInput id="dc-exp" v-model="docForm.expiry_date" type="date" label="Expiry date (optional)" icon="ti ti-calendar" :error="docForm.errors.expiry_date" />
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-ink">{{ editingDoc ? 'Replace file (optional)' : 'File' }}</label>
                    <input type="file" class="block w-full text-sm text-ink/70 file:mr-3 file:rounded-xl file:border-0 file:bg-tint file:px-4 file:py-2 file:text-sm file:font-semibold file:text-deep" @change="docForm.file = $event.target.files[0]" />
                    <p v-if="docForm.errors.file" class="mt-1 text-sm font-medium text-coral-deep">{{ docForm.errors.file }}</p>
                    <p class="mt-1 text-xs font-medium text-ink/40">PDF, image, or Word up to 10MB.</p>
                </div>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <FormButton type="button" variant="secondary" label="Cancel" @click="docOpen = false" />
                    <FormButton type="submit" variant="primary" :label="editingDoc ? 'Save' : 'Upload'" :loading="docForm.processing" :loading-label="editingDoc ? 'Saving…' : 'Uploading…'" />
                </div>
            </form>
        </AppModal>

        <AppModal :show="reissueOpen" title="Reissue payslip?" description="Creates a new issued copy. The original record stays on file." icon="ti ti-refresh" @close="reissueOpen = false">
            <form class="space-y-4" @submit.prevent="submitReissue">
                <p v-if="reissueTarget" class="text-sm font-medium text-ink/60">{{ reissueTarget.period_label }} · {{ money(reissueTarget.net_pay, reissueTarget.currency) }}</p>
                <FormTextarea id="ri-reason" v-model="reissueForm.reason" label="Reason" :error="reissueForm.errors.reason" required />
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <FormButton type="button" variant="secondary" label="Cancel" @click="reissueOpen = false" />
                    <FormButton type="submit" variant="primary" label="Reissue" :loading="reissueForm.processing" loading-label="Reissuing…" />
                </div>
            </form>
        </AppModal>

        <AdminDrawer :open="createCaseOpen" title="Open a case" eyebrow="Incident report" @close="createCaseOpen = false">
            <form class="space-y-4" @submit.prevent="submitCase">
                <p class="text-[13px] font-medium leading-relaxed text-ink/50">
                    Opening a case starts a formal record for {{ profile.name }}. Describe the incident as it is known now.
                </p>
                <FormSelect id="pc-owner" v-model="createCaseForm.owner_id" label="Case owner" icon="ti ti-user-check" :options="ownerOptions" searchable :error="createCaseForm.errors.owner_id" />
                <FormSelect id="pc-category" v-model="createCaseForm.category" label="Category" icon="ti ti-tag" :options="discipline.options?.categories || []" :error="createCaseForm.errors.category" />
                <FormTextInput v-if="createCaseForm.category === 'other'" id="pc-category-label" v-model="createCaseForm.category_label" label="Custom category" icon="ti ti-text-caption" :error="createCaseForm.errors.category_label" required />
                <FormSelect id="pc-severity" v-model="createCaseForm.severity" label="Initial severity" icon="ti ti-scale" :options="discipline.options?.severities || []" :error="createCaseForm.errors.severity" />
                <FormTextInput id="pc-incident" v-model="createCaseForm.incident_on" type="date" :max="today()" label="Date of incident" icon="ti ti-calendar" :error="createCaseForm.errors.incident_on" required />
                <FormTextarea id="pc-desc" v-model="createCaseForm.description" label="Incident description" :error="createCaseForm.errors.description" required />
                <FormTextarea id="pc-reason" v-model="createCaseForm.reason" label="Reason for opening this case" :error="createCaseForm.errors.reason" required />
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <FormButton type="button" variant="secondary" label="Cancel" @click="createCaseOpen = false" />
                    <FormButton type="submit" variant="primary" label="Open case" :loading="createCaseForm.processing" loading-label="Opening…" />
                </div>
            </form>
        </AdminDrawer>

        <DisciplinaryCaseDrawer
            :open="!!openCaseId"
            :row="openCaseRow"
            :panel="casePanel"
            @close="closeProfileCase"
            @refresh="refreshProfileCase"
            @updated="onCaseUpdated"
        />
        </div>
    </template>

<script setup>
import AppModal from '@/Components/App/AppModal.vue';
import AdminDrawer from '@/Components/Admin/AdminDrawer.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import DisciplinaryCaseDrawer from '@/Components/Admin/DisciplinaryCaseDrawer.vue';
import {
    disciplineSeverityMeta,
    disciplineStatusMeta,
    documentExpiryMeta,
    employmentStatusMeta,
    formatMoney,
    leaveDotClass,
    leaveStatusMeta,
    payslipStatusMeta,
    pillBase,
    ratingMeta,
} from '@/utils/hrStatus';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    profile: { type: Object, required: true },
    leave: { type: Object, required: true },
    performance: { type: Object, required: true },
    documents: { type: Array, default: () => [] },
    documentTypes: { type: Array, default: () => [] },
    checklists: { type: Array, default: () => [] },
    checklistTemplates: { type: Array, default: () => [] },
    payroll: { type: Object, default: null },
    discipline: { type: Object, default: null },
    can: { type: Object, required: true },
});

const activeTab = ref('overview');

const visibleTabs = computed(() => {
    const tabs = [
        { key: 'overview', label: 'Overview', icon: 'ti ti-user' },
        { key: 'leave', label: 'Leave', icon: 'ti ti-calendar-stats' },
    ];
    if (props.can.payroll_view) {
        tabs.push({ key: 'payroll', label: 'Payroll', icon: 'ti ti-cash' });
    }
    if (props.can.discipline_view) {
        tabs.push({ key: 'discipline', label: 'Cases', icon: 'ti ti-clipboard-text' });
    }
    tabs.push({ key: 'performance', label: 'Performance', icon: 'ti ti-notes' });
    tabs.push({ key: 'documents', label: 'Documents', icon: 'ti ti-files' });
    return tabs;
});

const setTab = (key) => {
    activeTab.value = key;
    const url = new URL(window.location.href);
    url.searchParams.set('tab', key);
    window.history.replaceState({}, '', url);
};

onMounted(() => {
    const tab = new URLSearchParams(window.location.search).get('tab');
    if (tab && visibleTabs.value.some((t) => t.key === tab)) {
        activeTab.value = tab;
    }
});

const profileEyebrow = computed(() => {
    const position = props.profile.position || 'Position not set';
    return props.profile.department ? `${position} · ${props.profile.department}` : position;
});
const statusMeta = computed(() => employmentStatusMeta(props.profile.display_status));
const leaveDot = (token) => leaveDotClass(token);
const leaveStatus = (status) => leaveStatusMeta(status);
const payslipStatus = (status) => payslipStatusMeta(status);
const disciplineStatus = (status) => disciplineStatusMeta(status);
const disciplineSeverity = (severity) => disciplineSeverityMeta(severity);
const caseHistoryHint = computed(() => {
    const cases = props.discipline?.cases || [];
    if (!cases.length) {
        return 'Open a case when an incident needs a permanent record.';
    }
    const openCount = cases.filter((item) => item.is_open).length;
    if (openCount) {
        return `${openCount} open · ${cases.length} on file. Open a row to record an action or decide an appeal.`;
    }
    return `${cases.length} on file. History is kept even after cases close.`;
});
const page = usePage();
const busyKey = ref('');
const compensationHistory = computed(() => props.payroll?.history || []);
const documentOptions = computed(() => [
    { value: '', label: 'None' },
    ...props.documents.map((doc) => ({ value: doc.id, label: doc.title })),
]);
const ratingBadge = (rating) => ratingMeta(rating);
const expiryBadge = (state) => documentExpiryMeta(state);
const money = (amount, currency) => formatMoney(amount, currency);

const employmentTypes = [
    { value: 'Full-time', label: 'Full-time' },
    { value: 'Part-time', label: 'Part-time' },
    { value: 'Contract', label: 'Contract' },
    { value: 'Intern', label: 'Intern' },
];
const employmentStatuses = [
    { value: 'active', label: 'Active' },
    { value: 'exited', label: 'Exited' },
];
const payFrequencies = [
    { value: 'monthly', label: 'Monthly' },
    { value: 'biweekly', label: 'Bi-weekly' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'annual', label: 'Annual' },
];
const ratingOptions = [
    { value: 'exceeding', label: 'Exceeding' },
    { value: 'meeting', label: 'Meeting' },
    { value: 'needs_improvement', label: 'Needs improvement' },
];
const leaveTypeOptions = computed(() => props.leave.types.map((t) => ({ value: t.id, label: t.name })));

const docTypeLabel = (type) => props.documentTypes.find((t) => t.value === type)?.label || type;
const expiringDocs = computed(() => props.documents.filter((d) => ['expiring', 'expired'].includes(d.expiry_state)));

const today = () => new Date().toISOString().slice(0, 10);
const DATE_MIN = '1950-01-01';
const DATE_MAX = '2100-12-31';

// A native <input type="date"> accepts 6-digit years; treat only a well-formed,
// in-range ISO date as valid so day counts never explode.
const isValidDate = (value) => /^\d{4}-\d{2}-\d{2}$/.test(value || '') && value >= DATE_MIN && value <= DATE_MAX;

// Profile
const profileOpen = ref(false);
const profileForm = useForm({
    position: '', department: '', employment_type: '', employment_status: 'active',
    exit_date: '', exit_reason: '', start_date: '',
    personal_email: '', personal_phone: '', home_address: '', date_of_birth: '',
    emergency_contact_name: '', emergency_contact_phone: '', emergency_contact_relationship: '',
});
const openProfileModal = () => {
    const p = props.profile;
    profileForm.clearErrors();
    profileForm.position = p.position || '';
    profileForm.department = p.department || '';
    profileForm.employment_type = p.employment_type || '';
    profileForm.employment_status = p.employment_status === 'exited' ? 'exited' : 'active';
    profileForm.exit_date = p.exit_date || today();
    profileForm.exit_reason = p.exit_reason || '';
    profileForm.start_date = p.start_date || '';
    profileForm.personal_email = p.personal_email || '';
    profileForm.personal_phone = p.personal_phone || '';
    profileForm.home_address = p.home_address || '';
    profileForm.date_of_birth = p.date_of_birth || '';
    profileForm.emergency_contact_name = p.emergency_contact_name || '';
    profileForm.emergency_contact_phone = p.emergency_contact_phone || '';
    profileForm.emergency_contact_relationship = p.emergency_contact_relationship || '';
    profileOpen.value = true;
};
const submitProfile = () => profileForm.post(route('admin.hr.staff.profile', props.profile.id), {
    preserveScroll: true,
    onSuccess: () => { profileOpen.value = false; },
});

// Exit
const exitOpen = ref(false);
const exitForm = useForm({ exit_date: today(), exit_reason: '' });
const submitExit = () => exitForm.post(route('admin.hr.staff.exit', props.profile.id), {
    preserveScroll: true,
    onSuccess: () => { exitOpen.value = false; },
});
const reactivateBusy = ref(false);
const reactivate = () => {
    reactivateBusy.value = true;
    router.post(route('admin.hr.staff.reactivate', props.profile.id), {}, {
        preserveScroll: true,
        onFinish: () => { reactivateBusy.value = false; },
    });
};

// Checklists
const checklistTemplateId = ref('');
const attachChecklist = () => {
    if (!checklistTemplateId.value || busyKey.value === 'checklist') return;
    busyKey.value = 'checklist';
    router.post(route('admin.hr.checklists.attach', props.profile.id), { checklist_template_id: checklistTemplateId.value }, {
        preserveScroll: true,
        onSuccess: () => { checklistTemplateId.value = ''; },
        onFinish: () => { busyKey.value = ''; },
    });
};
const toggleChecklistItem = (item) => {
    if (!props.can.manage || busyKey.value === `check-${item.id}`) return;
    busyKey.value = `check-${item.id}`;
    router.post(route('admin.hr.checklists.toggle', item.id), {}, {
        preserveScroll: true,
        onFinish: () => { busyKey.value = ''; },
    });
};

// Leave
const leaveOpen = ref(false);
const leaveForm = useForm({ leave_type_id: '', start_date: today(), end_date: today(), note: '', approve_now: true, override_negative: false });
const openLeaveModal = () => {
    leaveForm.reset();
    leaveForm.clearErrors();
    leaveForm.start_date = today();
    leaveForm.end_date = today();
    leaveForm.approve_now = !!props.can.leave;
    leaveOpen.value = true;
};
const leaveDayCount = computed(() => {
    if (!isValidDate(leaveForm.start_date) || !isValidDate(leaveForm.end_date)) return 0;
    const s = new Date(leaveForm.start_date);
    const e = new Date(leaveForm.end_date);
    if (e < s) return 0;
    return Math.round((e - s) / 86400000) + 1;
});
const submitLeave = () => leaveForm.post(route('admin.hr.leave.store', props.profile.id), {
    preserveScroll: true,
    onSuccess: () => { leaveOpen.value = false; },
});

// Allocation
const allocateOpen = ref(false);
const allocateForm = useForm({ leave_type_id: '', remaining_days: 0, year: props.leave.year, reason: '' });
const openAllocateModal = () => {
    allocateForm.reset();
    allocateForm.clearErrors();
    allocateForm.year = props.leave.year;
    allocateForm.remaining_days = props.leave.balances?.[0]?.remaining_days ?? 0;
    allocateForm.leave_type_id = props.leave.balances?.[0]?.leave_type_id || '';
    allocateForm.reason = '';
    allocateOpen.value = true;
};
const allocatePreview = computed(() => {
    const balance = props.leave.balances.find((row) => String(row.leave_type_id) === String(allocateForm.leave_type_id));
    if (!balance) return '';
    const remaining = Number(allocateForm.remaining_days || 0);
    const allowance = balance.used_days + remaining;
    return `Currently ${balance.remaining_days} remaining of ${balance.allowance_days}. Saving sets remaining to ${remaining} (allowance ${allowance}, ${balance.used_days} already used).`;
});
watch(() => allocateForm.leave_type_id, (id) => {
    const balance = props.leave.balances.find((row) => String(row.leave_type_id) === String(id));
    if (balance) {
        allocateForm.remaining_days = balance.remaining_days;
    }
});
const submitAllocation = () => allocateForm.post(route('admin.hr.leave.allocate', props.profile.id), {
    preserveScroll: true,
    onSuccess: () => { allocateOpen.value = false; },
});

// Decision (approve/reject)
const decisionOpen = ref(false);
const decisionMode = ref('approve');
const decisionTarget = ref(null);
const decisionForm = useForm({ comment: '', override_negative: false });
const openDecision = (req, mode) => {
    decisionTarget.value = req;
    decisionMode.value = mode;
    decisionForm.reset();
    decisionForm.clearErrors();
    decisionOpen.value = true;
};
const submitDecision = () => {
    const routeName = decisionMode.value === 'approve' ? 'admin.hr.leave.approve' : 'admin.hr.leave.reject';
    decisionForm.post(route(routeName, decisionTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => { decisionOpen.value = false; },
    });
};
const cancelLeave = (req) => {
    if (busyKey.value === `cancel-${req.id}`) return;
    busyKey.value = `cancel-${req.id}`;
    router.post(route('admin.hr.leave.cancel', req.id), {}, {
        preserveScroll: true,
        onFinish: () => { busyKey.value = ''; },
    });
};

// Compensation
const compOpen = ref(false);
const compForm = useForm({ base_salary: 0, pay_frequency: 'monthly', currency: 'NGN', effective_from: today(), note: '', reason: '', allowances: [] });
const openCompModal = () => {
    const c = props.payroll?.compensation;
    compForm.clearErrors();
    compForm.base_salary = c?.base_salary ?? 0;
    compForm.pay_frequency = c?.pay_frequency ?? 'monthly';
    compForm.currency = c?.currency ?? 'NGN';
    compForm.effective_from = today();
    compForm.note = c?.note ?? '';
    compForm.reason = '';
    compForm.allowances = c?.allowances ? c.allowances.map((a) => ({ label: a.label, amount: a.amount })) : [];
    compOpen.value = true;
};

const allowanceOpen = ref(false);
const endingAllowance = ref(null);
const editingAllowance = ref(null);
const allowanceForm = useForm({ label: '', amount: 0, effective_from: today(), reason: '' });
const allowanceDrawerTitle = computed(() => {
    if (endingAllowance.value) return `End ${endingAllowance.value.label}`;
    if (editingAllowance.value) return `Edit ${editingAllowance.value.label}`;
    return 'Add allowance';
});
const allowanceSubmitLabel = computed(() => {
    if (endingAllowance.value) return 'End allowance';
    if (editingAllowance.value) return 'Save allowance';
    return 'Add allowance';
});
const openAllowanceDrawer = (existing = null) => {
    endingAllowance.value = null;
    editingAllowance.value = existing;
    allowanceForm.reset();
    allowanceForm.clearErrors();
    allowanceForm.effective_from = today();
    if (existing) {
        allowanceForm.label = existing.label;
        allowanceForm.amount = existing.amount;
        allowanceForm.reason = `Updated ${existing.label}`;
    }
    allowanceOpen.value = true;
};
const openEndAllowance = (allowance) => {
    endingAllowance.value = allowance;
    editingAllowance.value = null;
    allowanceForm.reset();
    allowanceForm.clearErrors();
    allowanceForm.effective_from = today();
    allowanceForm.reason = `Ended ${allowance.label}`;
    allowanceOpen.value = true;
};
const nextAllowances = () => {
    const current = props.payroll?.compensation;
    if (!current) return [];
    if (endingAllowance.value) {
        return current.allowances
            .filter((item) => item.id !== endingAllowance.value.id)
            .map((item) => ({ label: item.label, amount: item.amount }));
    }
    if (editingAllowance.value) {
        return current.allowances.map((item) => (
            item.id === editingAllowance.value.id
                ? { label: allowanceForm.label, amount: allowanceForm.amount }
                : { label: item.label, amount: item.amount }
        ));
    }
    return [...current.allowances.map((item) => ({ label: item.label, amount: item.amount })), { label: allowanceForm.label, amount: allowanceForm.amount }];
};
const submitAllowanceChange = () => {
    const current = props.payroll?.compensation;
    if (!current) {
        allowanceForm.setError('reason', 'Set a salary before managing allowances.');
        return;
    }
    compForm.base_salary = current.base_salary;
    compForm.pay_frequency = current.pay_frequency;
    compForm.currency = current.currency;
    compForm.effective_from = allowanceForm.effective_from;
    compForm.reason = allowanceForm.reason;
    compForm.allowances = nextAllowances();
    compForm.post(route('admin.hr.compensation.update', props.profile.id), {
        preserveScroll: true,
        onSuccess: () => { allowanceOpen.value = false; },
    });
};
const submitComp = () => compForm.post(route('admin.hr.compensation.update', props.profile.id), {
    preserveScroll: true,
    onSuccess: () => { compOpen.value = false; },
});

// Payslip
const payslipOpen = ref(false);
const payslipForm = useForm({ period_label: '', period_start: today(), period_end: today(), base_pay: 0, notes: '', allowances: [], deductions: [] });
const openPayslipModal = () => {
    const c = props.payroll?.compensation;
    const now = new Date();
    const start = new Date(now.getFullYear(), now.getMonth(), 1);
    const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    const iso = (date) => date.toISOString().slice(0, 10);
    payslipForm.reset();
    payslipForm.clearErrors();
    payslipForm.period_label = start.toLocaleString('en-GB', { month: 'long', year: 'numeric' });
    payslipForm.period_start = iso(start);
    payslipForm.period_end = iso(end);
    payslipForm.base_pay = c?.base_salary ?? 0;
    payslipForm.allowances = c?.allowances ? c.allowances.map((a) => ({ label: a.label, amount: a.amount })) : [];
    payslipForm.deductions = [];
    payslipOpen.value = true;
};
const viewingPayslip = ref(null);
const openPayslipView = (slip) => { viewingPayslip.value = slip; };
const payslipNet = computed(() => {
    const base = Number(payslipForm.base_pay || 0);
    const allow = payslipForm.allowances.reduce((s, a) => s + Number(a.amount || 0), 0);
    const ded = payslipForm.deductions.reduce((s, d) => s + Number(d.amount || 0), 0);
    return base + allow - ded;
});
const submitPayslip = () => payslipForm.post(route('admin.hr.payslips.store', props.profile.id), {
    preserveScroll: true,
    onSuccess: () => { payslipOpen.value = false; },
});
const setPayslipStatus = (slip, status) => {
    const key = status === 'paid' ? `paid-${slip.id}` : `issue-${slip.id}`;
    if (busyKey.value === key) return;
    busyKey.value = key;
    router.post(route('admin.hr.payslips.status', slip.id), { status }, {
        preserveScroll: true,
        onFinish: () => { busyKey.value = ''; },
    });
};
const reissueOpen = ref(false);
const reissueTarget = ref(null);
const reissueForm = useForm({ reason: '' });
const openReissue = (slip) => {
    viewingPayslip.value = null;
    reissueTarget.value = slip;
    reissueForm.reset();
    reissueForm.clearErrors();
    reissueForm.reason = '';
    reissueOpen.value = true;
};
const submitReissue = () => {
    if (!reissueTarget.value) return;
    reissueForm.post(route('admin.hr.payslips.reissue', reissueTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => { reissueOpen.value = false; },
    });
};
const deletePayslip = (slip) => {
    if (confirm('Delete this draft payslip?')) {
        router.delete(route('admin.hr.payslips.destroy', slip.id), { preserveScroll: true });
    }
};

// Performance
const noteOpen = ref(false);
const noteForm = useForm({ noted_on: today(), rating: '', body: '' });
const openNoteModal = () => { noteForm.reset(); noteForm.clearErrors(); noteForm.noted_on = today(); noteOpen.value = true; };
const submitNote = () => noteForm.post(route('admin.hr.performance.store', props.profile.id), {
    preserveScroll: true,
    onSuccess: () => { noteOpen.value = false; },
});
const deleteNote = (note) => {
    if (confirm('Remove this performance note?')) {
        router.delete(route('admin.hr.performance.destroy', note.id), { preserveScroll: true });
    }
};

// Documents
const docOpen = ref(false);
const editingDoc = ref(null);
const docForm = useForm({ title: '', type: 'contract', expiry_date: '', file: null });
const openDocModal = () => {
    editingDoc.value = null;
    docForm.reset();
    docForm.clearErrors();
    docForm.type = 'contract';
    docForm.expiry_date = '';
    docOpen.value = true;
};
const openDocEdit = (doc) => {
    editingDoc.value = doc;
    docForm.clearErrors();
    docForm.title = doc.title;
    docForm.type = doc.type;
    docForm.expiry_date = doc.expiry_date || '';
    docForm.file = null;
    docOpen.value = true;
};
const submitDoc = () => {
    if (editingDoc.value) {
        docForm.patch(route('admin.hr.documents.update', editingDoc.value.id), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => { docOpen.value = false; editingDoc.value = null; },
        });
        return;
    }
    docForm.post(route('admin.hr.documents.store', props.profile.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => { docOpen.value = false; },
    });
};
const deleteDoc = (doc) => {
    if (confirm('Remove this document?')) {
        router.delete(route('admin.hr.documents.destroy', doc.id), { preserveScroll: true });
    }
};

const ownerOptions = computed(() => (props.discipline?.owners || []).map((person) => ({ value: person.id, label: person.name })));
const createCaseOpen = ref(false);
const createCaseForm = useForm({
    user_id: props.profile.id,
    owner_id: page.props.auth?.user?.id || '',
    category: 'conduct',
    category_label: '',
    severity: 'moderate',
    incident_on: today(),
    description: '',
    reason: '',
    return: 'profile',
});
const openCreateCase = () => {
    createCaseForm.reset();
    createCaseForm.clearErrors();
    createCaseForm.user_id = props.profile.id;
    createCaseForm.owner_id = page.props.auth?.user?.id || '';
    createCaseForm.incident_on = today();
    createCaseForm.return = 'profile';
    createCaseOpen.value = true;
};
const submitCase = () => createCaseForm.post(route('admin.hr.discipline.store'), {
    preserveScroll: true,
    onSuccess: () => { createCaseOpen.value = false; },
});

const openCaseId = ref(null);
const openCaseRow = ref(null);
const casePanel = ref(null);
const caseCache = new Map();

const fetchCasePanel = async (id) => {
    if (caseCache.has(id)) {
        casePanel.value = caseCache.get(id);
    }
    const { data } = await axios.get(route('admin.hr.discipline.show', id), {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    caseCache.set(id, data);
    if (openCaseId.value === id) {
        casePanel.value = data;
    }
};

const openProfileCase = (item) => {
    openCaseId.value = item.id;
    openCaseRow.value = item;
    const url = new URL(window.location.href);
    url.searchParams.set('tab', 'discipline');
    url.searchParams.set('case', String(item.id));
    window.history.replaceState({}, '', url);
    fetchCasePanel(item.id).catch(() => {});
};

const closeProfileCase = () => {
    openCaseId.value = null;
    openCaseRow.value = null;
    const url = new URL(window.location.href);
    url.searchParams.delete('case');
    window.history.replaceState({}, '', url);
};

const refreshProfileCase = () => {
    if (openCaseId.value) {
        caseCache.delete(openCaseId.value);
        fetchCasePanel(openCaseId.value).catch(() => {});
    }
};

const onCaseUpdated = (record) => {
    if (record?.id && openCaseRow.value?.id === record.id) {
        openCaseRow.value = { ...openCaseRow.value, ...record };
    }
};

onMounted(() => {
    const caseId = Number(new URLSearchParams(window.location.search).get('case') || props.discipline?.opened_id || 0);
    if (caseId) {
        const row = (props.discipline?.cases || []).find((item) => item.id === caseId);
        if (row) {
            openProfileCase(row);
        }
    }
});

</script>
