<template>
    <AdminDrawer :open="!!person" size="lg" @close="$emit('close')">
        <template #header>
            <div v-if="shown" class="flex items-start gap-3">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-tint text-sm font-bold text-deep">
                    {{ shown.initials || initials(shown.name) }}
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="truncate text-[17px] font-bold tracking-tight text-ink">{{ shown.name }}</h2>
                    <p class="mt-0.5 truncate text-[13px] font-medium text-ink/45">{{ shown.email }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                        <span
                            class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                            :class="statusClass(shown.status)"
                        >
                            {{ shown.status_label }}
                        </span>
                        <span
                            v-if="shown.on_leave"
                            class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-800"
                        >
                            On leave
                        </span>
                        <span class="text-[12px] font-medium text-ink/40">
                            {{ shown.password_set ? `Joined ${shown.joined}` : `Invited ${shown.joined}` }}
                        </span>
                    </div>
                </div>
                <button
                    type="button"
                    class="tap-target flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-ink/40 hover:bg-pale hover:text-ink"
                    aria-label="Close"
                    @click="$emit('close')"
                >
                    <i class="ti ti-x text-lg" aria-hidden="true" />
                </button>
            </div>
        </template>

        <div v-if="shown" class="space-y-5">
            <div class="flex rounded-full bg-pale p-1">
                <button
                    v-for="item in tabs"
                    :key="item.id"
                    type="button"
                    class="flex-1 rounded-full px-3 py-1.5 text-[13px] font-semibold transition-all duration-200 ease-[cubic-bezier(0.32,0.72,0,1)]"
                    :class="tab === item.id ? 'bg-white text-ink shadow-sm' : 'text-ink/40 hover:text-ink'"
                    @click="tab = item.id"
                >
                    {{ item.label }}
                </button>
            </div>

            <div v-if="loading" class="space-y-3" aria-hidden="true">
                <div class="h-4 w-32 animate-pulse rounded bg-pale" />
                <div class="h-10 animate-pulse rounded-xl bg-pale" />
                <div class="h-24 animate-pulse rounded-xl bg-pale" />
            </div>

            <Transition v-if="!loading" name="admin-pane" mode="out-in">
            <div v-if="tab === 'overview'" key="overview" class="space-y-6">
                <section>
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-[13px] font-bold text-ink">Roles</h3>
                        <FormButton
                            variant="primary"
                            class="!rounded-xl !px-3 !py-2 !text-[12px]"
                            label="Change roles"
                            icon-left="ti ti-shield"
                            @click="openRoles"
                        />
                    </div>

                    <div v-if="currentRoles.length" class="mt-3 flex flex-wrap gap-1.5">
                        <span
                            v-for="role in currentRoles"
                            :key="role.id"
                            class="inline-flex items-center rounded-full bg-tint px-2.5 py-1 text-[12px] font-semibold text-deep"
                        >
                            {{ role.name }}
                        </span>
                    </div>
                    <p v-else class="mt-3 rounded-xl bg-pale px-3 py-3 text-[13px] font-medium text-ink/50">
                        No roles assigned. They can sign in, but the console stays restricted until you give them a role.
                    </p>
                    <p v-if="shown.is_super && !isSelf" class="mt-2 text-[12px] font-medium text-ink/40">
                        Changing Super Admin access is logged as a high-trust action.
                    </p>
                </section>

                <dl class="grid gap-3 text-[13px] sm:grid-cols-2">
                    <div>
                        <dt class="font-semibold text-ink/40">Last login</dt>
                        <dd class="mt-0.5 font-medium text-ink">{{ shown.last_login || 'Never' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-ink/40">Last logout</dt>
                        <dd class="mt-0.5 font-medium text-ink">{{ shown.last_logout || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-ink/40">Invite expiry</dt>
                        <dd class="mt-0.5 font-medium text-ink">
                            <template v-if="shown.status === 'invited'">
                                {{ shown.invite_expired ? 'Expired' : shown.invite_expires_at || '—' }}
                            </template>
                            <template v-else>—</template>
                        </dd>
                    </div>
                    <div v-if="shown.attendance">
                        <dt class="font-semibold text-ink/40">Live idle</dt>
                        <dd class="mt-0.5 font-medium text-ink">{{ shown.attendance.idle_label }} without interaction</dd>
                    </div>
                    <div v-if="detail.invited_by">
                        <dt class="font-semibold text-ink/40">Invited by</dt>
                        <dd class="mt-0.5 font-medium text-ink">{{ detail.invited_by.name }}</dd>
                    </div>
                    <div v-if="detail.suggested_role">
                        <dt class="font-semibold text-ink/40">Suggested role</dt>
                        <dd class="mt-0.5 font-medium text-ink">{{ detail.suggested_role }}</dd>
                    </div>
                    <div v-if="adherenceView" class="sm:col-span-2 space-y-3 rounded-xl bg-pale px-3 py-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Shift adherence</p>
                                <p class="mt-1 text-[13px] font-semibold text-ink">{{ adherenceView.date_label }}</p>
                            </div>
                            <div class="inline-flex flex-wrap items-center gap-2 text-[12px] font-semibold text-ink/50">
                                <span>Date</span>
                                <input
                                    v-model="adherenceDateDraft"
                                    type="date"
                                    :max="todayIso"
                                    class="rounded-lg border-0 bg-white px-2 py-1 text-[12px] font-semibold text-ink outline-none ring-1 ring-ink/10 focus:ring-base/30"
                                />
                                <button
                                    type="button"
                                    class="rounded-lg bg-base-action px-2.5 py-1 text-[11px] font-bold text-white transition-colors duration-150 hover:bg-base-hover disabled:opacity-50"
                                    :disabled="adherenceDateDraft === props.adherenceDate"
                                    @click="applyAdherenceDate"
                                >
                                    Apply
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide"
                                :class="adherenceStatusClass(adherenceView.summary?.status)"
                            >
                                {{ adherenceView.summary?.status_label || '—' }}
                            </span>
                            <span v-if="adherenceView.summary?.adherence_pct != null" class="text-[12px] font-medium text-ink/50">
                                {{ adherenceView.summary.adherence_pct }}% on floor
                            </span>
                            <span v-if="adherenceView.on_leave" class="text-[12px] font-medium text-amber-700">On approved leave</span>
                            <span v-else-if="!adherenceView.scheduled" class="text-[12px] font-medium text-ink/45">Not scheduled this day</span>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-3">
                            <div class="rounded-xl bg-white px-3 py-2.5 ring-1 ring-ink/[0.05]">
                                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-ink/35">On floor</p>
                                <p class="mt-1 text-[15px] font-bold text-ink">
                                    {{ adherenceView.summary?.adherence_pct != null ? `${adherenceView.summary.adherence_pct}%` : '—' }}
                                </p>
                            </div>
                            <div class="rounded-xl bg-white px-3 py-2.5 ring-1 ring-ink/[0.05]">
                                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-ink/35">Total idle</p>
                                <p class="mt-1 text-[15px] font-bold text-coral-deep">
                                    {{ adherenceView.summary?.idle_total_label || '0s' }}
                                </p>
                                <p v-if="adherenceView.summary?.idle_events_count" class="mt-0.5 text-[11px] font-medium text-ink/40">
                                    {{ adherenceView.summary.idle_events_count }} over 7m
                                </p>
                            </div>
                            <div class="rounded-xl bg-white px-3 py-2.5 ring-1 ring-ink/[0.05]">
                                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-ink/35">Sign in</p>
                                <p class="mt-1 text-[15px] font-bold text-ink">{{ adherenceView.summary?.signed_in_at || '—' }}</p>
                            </div>
                        </div>

                        <p class="text-[13px] font-medium text-ink">
                            {{ adherenceView.summary?.signed_out_at || '—' }}
                        </p>
                        <p class="text-[12px] font-medium text-ink/45">
                            {{ adherenceView.shift?.label }}
                            <span v-if="adherenceView.shift?.breaks?.length">
                                · breaks
                                {{ adherenceView.shift.breaks.map((item) => `${item.start}–${item.end}`).join(', ') }}
                            </span>
                        </p>

                        <div v-if="adherenceView.timeline?.length" class="space-y-2">
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Day timeline</p>
                            <div class="relative h-3 overflow-hidden rounded-full bg-white ring-1 ring-ink/[0.06]">
                                <div
                                    v-for="(segment, index) in adherenceView.timeline"
                                    :key="`${segment.type}-${segment.start}-${index}`"
                                    class="absolute top-0 h-full"
                                    :style="{ left: `${segment.start_pct}%`, width: `${Math.max(segment.width_pct, 1.5)}%` }"
                                    :class="timelineClass(segment.type)"
                                    :title="`${segment.label} · ${segment.start}–${segment.end}`"
                                />
                            </div>
                            <ul class="grid gap-1.5 sm:grid-cols-2">
                                <li
                                    v-for="(segment, index) in adherenceView.timeline"
                                    :key="`legend-${segment.type}-${segment.start}-${index}`"
                                    class="flex items-center gap-2 text-[12px] font-medium text-ink/55"
                                >
                                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" :class="timelineClass(segment.type)" />
                                    <span>{{ segment.label }} · {{ segment.start }}–{{ segment.end }}</span>
                                </li>
                            </ul>
                        </div>

                        <div v-if="adherenceView.idle_events?.length">
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Idle over 7m</p>
                            <ul class="mt-2 divide-y divide-ink/[0.06] rounded-xl bg-white ring-1 ring-ink/[0.05]">
                                <li
                                    v-for="event in adherenceView.idle_events"
                                    :key="event.id"
                                    class="flex items-center justify-between gap-3 px-3 py-2.5 text-[13px]"
                                >
                                    <span class="font-medium text-ink">{{ event.start }}–{{ event.end || 'now' }}</span>
                                    <span class="font-semibold text-coral-deep">{{ event.duration_label }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div v-else-if="shown.attendance" class="sm:col-span-2 rounded-xl bg-pale px-3 py-3">
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Today vs shift</p>
                        <p class="mt-1.5 text-[13px] font-medium text-ink">
                            Should sign in {{ shown.attendance.expected_in }}
                            · {{ shown.attendance.logged_in }}
                            <span
                                v-if="shown.attendance.on_shift_now"
                                class="ms-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700"
                            >
                                On shift
                            </span>
                            <span v-if="shown.attendance.late" class="ms-1 font-bold text-amber-700">Late</span>
                            <span v-if="shown.attendance.missed" class="ms-1 font-bold text-coral-deep">Not signed in</span>
                        </p>
                        <p class="mt-0.5 text-[13px] font-medium text-ink/50">
                            {{ shown.attendance.shift_label }}
                            · last activity {{ shown.attendance.last_activity }}
                            · {{ shown.attendance.logged_out }}
                        </p>
                    </div>
                    <div v-if="detail.suspension_reason" class="sm:col-span-2">
                        <dt class="font-semibold text-ink/40">Disabled because</dt>
                        <dd class="mt-0.5 font-medium text-ink">{{ detail.suspension_reason }}</dd>
                    </div>
                </dl>

                <div class="flex flex-wrap gap-x-4 gap-y-2">
                    <Link
                        v-if="canSeeHr"
                        :href="route('admin.hr.staff.show', shown.id)"
                        :show-progress="false"
                        class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-base-action hover:text-base-hover"
                    >
                        <i class="ti ti-id-badge-2" aria-hidden="true" />
                        Open HR profile
                    </Link>
                    <Link
                        v-if="canSeeDiscipline"
                        :href="route('admin.hr.discipline.index', { q: shown.name })"
                        :show-progress="false"
                        class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-base-action hover:text-base-hover"
                    >
                        <i class="ti ti-gavel" aria-hidden="true" />
                        Disciplinary cases
                    </Link>
                </div>
            </div>

            <div v-else-if="tab === 'shift'" key="shift" class="space-y-5">
                <p class="text-[13px] font-medium text-ink/50">
                    Set the days and hours this person should be on duty. Insights then compare that with when they actually sign in, sign out, and go idle.
                </p>
                <div>
                    <p class="text-[12px] font-semibold text-ink/50">Working days</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <button
                            v-for="day in dayOptions"
                            :key="day.id"
                            type="button"
                            class="rounded-full px-3 py-1.5 text-[12px] font-semibold"
                            :class="shiftForm.days.includes(day.id) ? 'bg-base-action text-white' : 'bg-pale text-ink/55 hover:bg-tint'"
                            @click="toggleShiftDay(day.id)"
                        >
                            {{ day.short }}
                        </button>
                    </div>
                    <p v-if="shiftForm.errors.shift_days" class="mt-1.5 text-[12px] font-medium text-coral-deep">
                        {{ shiftForm.errors.shift_days }}
                    </p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block">
                        <span class="text-[12px] font-semibold text-ink/50">Shift starts</span>
                        <input
                            v-model="shiftForm.start"
                            type="time"
                            class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                        />
                        <p v-if="shiftForm.errors.shift_starts_at" class="mt-1 text-[12px] font-medium text-coral-deep">
                            {{ shiftForm.errors.shift_starts_at }}
                        </p>
                    </label>
                    <label class="block">
                        <span class="text-[12px] font-semibold text-ink/50">Shift ends</span>
                        <input
                            v-model="shiftForm.end"
                            type="time"
                            class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                        />
                        <p v-if="shiftForm.errors.shift_ends_at" class="mt-1 text-[12px] font-medium text-coral-deep">
                            {{ shiftForm.errors.shift_ends_at }}
                        </p>
                    </label>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-[12px] font-semibold text-ink/50">Break windows</p>
                        <button
                            type="button"
                            class="text-[12px] font-semibold text-base-action hover:text-base-hover"
                            @click="addBreak"
                        >
                            Add break
                        </button>
                    </div>
                    <p class="mt-1 text-[12px] font-medium text-ink/40">
                        Scheduled breaks are excluded from idle logging and on-floor adherence.
                    </p>
                    <ul v-if="shiftForm.breaks.length" class="mt-3 space-y-2">
                        <li
                            v-for="(item, index) in shiftForm.breaks"
                            :key="index"
                            class="grid gap-2 rounded-xl bg-pale p-3 sm:grid-cols-[1fr_1fr_auto]"
                        >
                            <label class="block">
                                <span class="text-[11px] font-semibold text-ink/45">Start</span>
                                <input
                                    v-model="item.start"
                                    type="time"
                                    class="mt-1 w-full rounded-xl border border-ink/10 bg-white px-3 py-2 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                                />
                            </label>
                            <label class="block">
                                <span class="text-[11px] font-semibold text-ink/45">End</span>
                                <input
                                    v-model="item.end"
                                    type="time"
                                    class="mt-1 w-full rounded-xl border border-ink/10 bg-white px-3 py-2 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                                />
                            </label>
                            <button
                                type="button"
                                class="self-end rounded-xl px-3 py-2 text-[12px] font-semibold text-coral-deep hover:bg-white"
                                @click="removeBreak(index)"
                            >
                                Remove
                            </button>
                        </li>
                    </ul>
                    <p v-else class="mt-3 rounded-xl bg-pale px-3 py-3 text-[13px] font-medium text-ink/45">
                        No breaks configured.
                    </p>
                    <p v-if="shiftForm.errors.shift_breaks" class="mt-1.5 text-[12px] font-medium text-coral-deep">
                        {{ shiftForm.errors.shift_breaks }}
                    </p>
                </div>
                <FormButton
                    variant="primary"
                    class="!rounded-xl !px-4 !py-2.5 !text-[13px]"
                    label="Save shift"
                    :loading="busy === 'shift'"
                    loading-label="Saving…"
                    @click="saveShift"
                />
            </div>

            <div v-else key="activity" class="space-y-6">
                <section>
                    <h3 class="text-[13px] font-bold text-ink">Login history</h3>
                    <ul v-if="panel?.activity?.logins?.length" class="mt-2 divide-y divide-ink/[0.06]">
                        <li v-for="row in panel.activity.logins" :key="row.id" class="py-2.5 text-[13px]">
                            <p class="font-semibold text-ink">{{ row.title }}</p>
                            <p class="text-ink/40">{{ row.when }}<span v-if="row.ip"> · {{ row.ip }}</span></p>
                        </li>
                    </ul>
                    <p v-else class="mt-2 text-sm text-ink/40">No sign-ins recorded yet.</p>
                </section>

                <section>
                    <h3 class="text-[13px] font-bold text-ink">Admin activity</h3>
                    <p class="mt-0.5 text-[12px] font-medium text-ink/40">What they did in the admin panel.</p>
                    <ul v-if="panel?.activity?.actor_log?.length" class="mt-2 divide-y divide-ink/[0.06]">
                        <li v-for="row in panel.activity.actor_log" :key="row.id" class="py-2.5 text-[13px]">
                            <p class="font-semibold text-ink">{{ row.title }}</p>
                            <p class="text-ink/40">{{ row.when }}</p>
                        </li>
                    </ul>
                    <p v-else class="mt-2 text-sm text-ink/40">No admin actions recorded yet.</p>
                </section>

                <section>
                    <h3 class="text-[13px] font-bold text-ink">Account timeline</h3>
                    <p class="mt-0.5 text-[12px] font-medium text-ink/40">Access, HR, and case events for this person.</p>
                    <ul v-if="panel?.activity?.feed?.length" class="mt-2 divide-y divide-ink/[0.06]">
                        <li v-for="row in panel.activity.feed" :key="row.id" class="py-2.5 text-[13px]">
                            <p class="font-semibold text-ink">{{ row.title }}</p>
                            <p v-if="row.body" class="mt-0.5 font-medium text-ink/55">{{ row.body }}</p>
                            <p class="text-ink/40">
                                <span v-if="row.actor">{{ row.actor }} · </span>{{ row.when }}
                            </p>
                            <Link
                                v-if="row.href"
                                :href="row.href"
                                :show-progress="false"
                                class="mt-1 inline-flex text-[12px] font-semibold text-base-action hover:text-base-hover"
                            >
                                {{ row.href_label || 'Open' }}
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="mt-2 text-sm text-ink/40">Nothing on this timeline yet.</p>
                </section>
            </div>
            </Transition>
        </div>

        <template v-if="shown" #footer>
            <div class="grid grid-cols-2 gap-2">
                <FormButton
                    variant="primary"
                    class="w-full !rounded-xl !px-3 !py-2.5 !text-[13px]"
                    label="Message"
                    :loading="busy === 'message'"
                    @click="openMessage"
                />
                <FormButton
                    variant="secondary"
                    class="w-full !rounded-xl !px-3 !py-2.5 !text-[13px]"
                    label="Send notice"
                    :loading="busy === 'announce'"
                    @click="openAnnounce"
                />
                <FormButton
                    v-if="shown.password_set && !isSelf"
                    variant="secondary"
                    class="w-full !rounded-xl !px-3 !py-2.5 !text-[13px]"
                    label="Force logout"
                    :loading="busy === 'logout'"
                    @click="ask('logout')"
                />
                <FormButton
                    v-if="shown.password_set && !isSelf && shown.status !== 'suspended'"
                    variant="secondary"
                    class="w-full !rounded-xl !px-3 !py-2.5 !text-[13px]"
                    label="Reset password"
                    :loading="busy === 'reset'"
                    @click="ask('reset')"
                />
                <FormButton
                    v-if="shown.status === 'invited'"
                    variant="secondary"
                    class="w-full !rounded-xl !px-3 !py-2.5 !text-[13px]"
                    label="Resend invite"
                    :loading="busy === 'resend'"
                    @click="resendInvite"
                />
                <FormButton
                    v-if="shown.status === 'invited'"
                    variant="secondary"
                    class="w-full !rounded-xl !px-3 !py-2.5 !text-[13px] !border-red-200 !text-red-600"
                    label="Revoke invite"
                    :loading="busy === 'revoke'"
                    @click="ask('revoke')"
                />
                <FormButton
                    v-if="shown.status === 'active' && !isSelf"
                    variant="secondary"
                    class="w-full !rounded-xl !px-3 !py-2.5 !text-[13px] !border-red-200 !text-red-600"
                    label="Disable"
                    :loading="busy === 'disable'"
                    @click="ask('disable')"
                />
                <FormButton
                    v-if="shown.status === 'suspended' && !isSelf"
                    variant="secondary"
                    class="w-full !rounded-xl !px-3 !py-2.5 !text-[13px]"
                    label="Reinstate"
                    :loading="busy === 'reinstate'"
                    @click="ask('reinstate')"
                />
                <FormButton
                    v-if="shown.status !== 'invited' && !isSelf"
                    variant="secondary"
                    class="col-span-2 w-full !rounded-xl !px-3 !py-2.5 !text-[13px] !border-red-200 !text-red-600"
                    label="Remove account"
                    :loading="busy === 'remove'"
                    @click="ask('remove')"
                />
            </div>
            <p v-if="isSelf" class="mt-2 text-center text-[12px] font-medium text-ink/40">
                You cannot change your own access here.
            </p>
        </template>
    </AdminDrawer>

    <AdminDrawer :open="rolesOpen" title="Change roles" eyebrow="Access" @close="rolesOpen = false">
        <div class="space-y-3">
            <p class="text-[13px] font-medium text-ink/50">Select every role this person should have. Changes apply immediately.</p>
            <label class="flex items-center gap-3 rounded-xl bg-pale px-3 py-2.5">
                <input
                    v-model="roleDraft.super"
                    type="checkbox"
                    class="rounded border-ink/20 text-base-action"
                    :disabled="isSelf"
                />
                <span class="text-[13px] font-semibold text-ink">Super Admin</span>
            </label>
            <label
                v-for="role in roles"
                :key="role.id"
                class="flex items-center gap-3 rounded-xl px-3 py-2.5 ring-1 ring-ink/[0.06]"
            >
                <input v-model="roleDraft.ids" type="checkbox" :value="role.id" class="rounded border-ink/20 text-base-action" />
                <span class="text-[13px] font-semibold text-ink">{{ role.name }}</span>
            </label>
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <FormButton variant="secondary" class="!rounded-xl !px-4 !py-2.5 !text-[13px]" label="Cancel" @click="rolesOpen = false" />
                <FormButton
                    variant="primary"
                    class="!rounded-xl !px-4 !py-2.5 !text-[13px]"
                    label="Save roles"
                    :loading="busy === 'roles'"
                    loading-label="Saving…"
                    @click="saveRoles"
                />
            </div>
        </template>
    </AdminDrawer>

    <AdminDrawer :open="messageOpen" title="Message" eyebrow="Email and in-app" @close="messageOpen = false">
        <form class="space-y-4" @submit.prevent="submitMessage">
            <div class="flex flex-wrap gap-1.5">
                <button
                    v-for="item in channelOptions"
                    :key="item.value"
                    type="button"
                    class="rounded-full px-3 py-1.5 text-[13px] font-semibold transition-colors duration-150"
                    :class="messageForm.channels.includes(item.value) ? 'bg-base-action text-white' : 'bg-pale text-ink/55 hover:bg-tint'"
                    @click="toggleChannel(messageForm, item.value)"
                >
                    {{ item.label }}
                </button>
            </div>
            <FormTextInput id="staff-message-subject" v-model="messageForm.subject" label="Subject" :error="messageForm.errors.subject" />
            <FormTextarea id="staff-message-body" v-model="messageForm.body" label="Message" :error="messageForm.errors.body" required />
        </form>
        <template #footer>
            <div class="flex justify-end gap-2">
                <FormButton variant="secondary" class="!rounded-xl !px-4 !py-2.5 !text-[13px]" label="Cancel" @click="messageOpen = false" />
                <FormButton
                    variant="primary"
                    class="!rounded-xl !px-4 !py-2.5 !text-[13px]"
                    label="Send"
                    :loading="busy === 'message'"
                    loading-label="Sending…"
                    @click="submitMessage"
                />
            </div>
        </template>
    </AdminDrawer>

    <AdminDrawer :open="announceOpen" title="Send a notice" eyebrow="Template" @close="announceOpen = false">
        <form class="space-y-4" @submit.prevent="submitAnnounce">
            <label class="block">
                <span class="text-[12px] font-semibold text-ink/50">Template</span>
                <select
                    v-model="announceForm.template_id"
                    class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base"
                    @change="applyTemplate"
                >
                    <option value="">Pick a template…</option>
                    <option v-for="item in catalog" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
                </select>
            </label>
            <div class="flex flex-wrap gap-1.5">
                <button
                    v-for="item in channelOptions"
                    :key="item.value"
                    type="button"
                    class="rounded-full px-3 py-1.5 text-[13px] font-semibold transition-colors duration-150"
                    :class="announceForm.channels.includes(item.value) ? 'bg-base-action text-white' : 'bg-pale text-ink/55 hover:bg-tint'"
                    @click="toggleChannel(announceForm, item.value)"
                >
                    {{ item.label }}
                </button>
            </div>
            <FormTextInput id="staff-notice-subject" v-model="announceForm.subject" label="Subject" :error="announceForm.errors.subject" />
            <FormTextarea id="staff-notice-body" v-model="announceForm.body" label="Message" :error="announceForm.errors.body" required />
        </form>
        <template #footer>
            <div class="flex justify-end gap-2">
                <FormButton variant="secondary" class="!rounded-xl !px-4 !py-2.5 !text-[13px]" label="Cancel" @click="announceOpen = false" />
                <FormButton
                    variant="primary"
                    class="!rounded-xl !px-4 !py-2.5 !text-[13px]"
                    label="Send to this person"
                    :loading="busy === 'announce'"
                    loading-label="Sending…"
                    :disabled="!announceForm.template_id"
                    @click="submitAnnounce"
                />
            </div>
        </template>
    </AdminDrawer>

    <AdminConfirmDialog
        :open="!!dialog"
        :title="dialogMeta.title"
        :description="dialogMeta.description"
        :confirm-label="dialogMeta.confirmLabel"
        :tone="dialogMeta.tone"
        :require-reason="dialogMeta.requireReason"
        :processing="!!busy"
        @close="dialog = null"
        @confirm="runDialog"
    >
        <label v-if="dialog === 'remove'" class="mt-4 block">
            <span class="text-[12px] font-semibold text-ink/50">
                Type <span class="font-bold text-ink">{{ shown?.name }}</span> or their email to confirm
            </span>
            <input
                v-model="typedConfirm"
                type="text"
                required
                class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
            />
        </label>
    </AdminConfirmDialog>
</template>

<script setup>
import AdminConfirmDialog from '@/Components/Admin/AdminConfirmDialog.vue';
import AdminDrawer from '@/Components/Admin/AdminDrawer.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import { toast } from '@/utils/adminRange';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    person: { type: Object, default: null },
    panel: { type: Object, default: null },
    loading: { type: Boolean, default: false },
    roles: { type: Array, default: () => [] },
    templates: { type: Array, default: () => [] },
    weekdays: { type: Array, default: () => [] },
    adherenceDate: { type: String, default: () => new Date().toISOString().slice(0, 10) },
});

const emit = defineEmits(['close', 'updated', 'deleted', 'refresh', 'resend', 'adherence-date']);

const todayIso = new Date().toISOString().slice(0, 10);
const adherenceDateDraft = ref(props.adherenceDate || todayIso);

const page = usePage();
const tab = ref('overview');
const busy = ref('');
const dialog = ref(null);
const typedConfirm = ref('');
const lastPerson = ref(null);
const rolesOpen = ref(false);
const messageOpen = ref(false);
const announceOpen = ref(false);
const roleDraft = reactive({ ids: [], super: false });
const messageForm = reactive({
    subject: '',
    body: '',
    channels: ['in_app', 'email'],
    errors: {},
});
const announceForm = reactive({
    template_id: '',
    subject: '',
    body: '',
    channels: ['in_app', 'email'],
    errors: {},
});

const shown = computed(() => props.person || lastPerson.value);
const detail = computed(() => props.panel?.staff || shown.value || {});
const meId = computed(() => page.props.auth?.user?.id);
const isSelf = computed(() => !!shown.value && shown.value.id === meId.value);
const abilities = computed(() => page.props.auth?.user?.abilities || []);
const canSeeHr = computed(() => {
    const user = page.props.auth?.user;
    return !!user?.is_super_admin || abilities.value.includes('hr.view');
});
const canSeeDiscipline = computed(() => {
    const user = page.props.auth?.user;
    return !!user?.is_super_admin || abilities.value.includes('hr.discipline.view');
});

const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'shift', label: 'Shift' },
    { id: 'activity', label: 'Activity' },
];

const dayOptions = computed(() =>
    props.weekdays.length
        ? props.weekdays
        : [
            { id: 1, label: 'Monday', short: 'Mon' },
            { id: 2, label: 'Tuesday', short: 'Tue' },
            { id: 3, label: 'Wednesday', short: 'Wed' },
            { id: 4, label: 'Thursday', short: 'Thu' },
            { id: 5, label: 'Friday', short: 'Fri' },
            { id: 6, label: 'Saturday', short: 'Sat' },
            { id: 7, label: 'Sunday', short: 'Sun' },
        ],
);

const shiftForm = reactive({
    days: [1, 2, 3, 4, 5, 6],
    start: '08:00',
    end: '18:00',
    breaks: [],
    errors: {},
});

const adherenceView = computed(() => props.panel?.adherence || shown.value?.adherence || null);

watch(
    () => props.adherenceDate,
    (value) => {
        if (value) {
            adherenceDateDraft.value = value;
        }
    },
);

const applyAdherenceDate = () => {
    emit('adherence-date', adherenceDateDraft.value);
};

const adherenceStatusClass = (status) => {
    const map = {
        on_time: 'bg-emerald-50 text-emerald-700',
        in_progress: 'bg-emerald-50 text-emerald-700',
        late: 'bg-amber-50 text-amber-800',
        missed: 'bg-red-50 text-red-700',
        off_day: 'bg-white text-ink/45 ring-1 ring-ink/[0.06]',
        leave: 'bg-amber-50 text-amber-800',
        pending: 'bg-white text-ink/45 ring-1 ring-ink/[0.06]',
        inactive: 'bg-white text-ink/45 ring-1 ring-ink/[0.06]',
    };

    return map[status] || 'bg-white text-ink/45 ring-1 ring-ink/[0.06]';
};

const timelineClass = (type) => {
    const map = {
        expected: 'bg-ink/10',
        break: 'bg-amber-200',
        present: 'bg-emerald-400',
        idle: 'bg-coral-deep/80',
    };

    return map[type] || 'bg-base-action/30';
};

const currentRoles = computed(() => shown.value?.roles || []);
const catalog = computed(() => props.panel?.templates?.length ? props.panel.templates : props.templates);
const channelOptions = [
    { value: 'in_app', label: 'In-app' },
    { value: 'email', label: 'Email' },
];

const dialogMeta = computed(() => {
    const map = {
        disable: {
            title: 'Disable this account',
            description: shown.value?.is_super
                ? 'They will be signed out everywhere. Acting on another Super Admin is logged as a high-trust change.'
                : 'They will be signed out everywhere and cannot sign in until reinstated.',
            confirmLabel: 'Disable',
            tone: 'danger',
            requireReason: true,
        },
        reinstate: {
            title: 'Reinstate access',
            description: 'They can sign in again with their existing password.',
            confirmLabel: 'Reinstate',
            tone: 'default',
            requireReason: true,
        },
        revoke: {
            title: 'Revoke invite',
            description: 'The pending invite is removed. They never become active staff.',
            confirmLabel: 'Revoke',
            tone: 'danger',
            requireReason: true,
        },
        logout: {
            title: 'Sign them out everywhere?',
            description: 'Active sessions and remember-me tokens are cleared. The account stays enabled.',
            confirmLabel: 'Force logout',
            tone: 'danger',
            requireReason: true,
        },
        reset: {
            title: 'Send a password reset?',
            description: `A branded reset email goes to ${shown.value?.email}.`,
            confirmLabel: 'Send reset',
            tone: 'default',
            requireReason: false,
        },
        remove: {
            title: 'Remove this staff account',
            description: 'This removes admin access only. HR and disciplinary history are kept. Type their name or email to confirm.',
            confirmLabel: 'Remove',
            tone: 'danger',
            requireReason: true,
        },
    };
    return map[dialog.value] || { title: 'Confirm', description: '', confirmLabel: 'Confirm', requireReason: true };
});

const hydrateShift = (person) => {
    const shift = person?.shift || person?.attendance;
    shiftForm.days = [...(shift?.days || [1, 2, 3, 4, 5, 6])];
    shiftForm.start = shift?.start || '08:00';
    shiftForm.end = shift?.end || '18:00';
    shiftForm.breaks = (shift?.breaks || []).map((item) => ({
        start: item.start,
        end: item.end,
        label: item.label || 'Break',
    }));
    shiftForm.errors = {};
};

const addBreak = () => {
    if (shiftForm.breaks.length >= 5) {
        return;
    }

    shiftForm.breaks.push({ start: '12:00', end: '13:00', label: 'Break' });
};

const removeBreak = (index) => {
    shiftForm.breaks.splice(index, 1);
};

watch(
    () => props.person?.id,
    (id, previous) => {
        if (props.person) {
            lastPerson.value = props.person;
        }
        if (id === previous) {
            return;
        }
        tab.value = 'overview';
        dialog.value = null;
        rolesOpen.value = false;
        messageOpen.value = false;
        announceOpen.value = false;
        typedConfirm.value = '';
        busy.value = '';
        hydrateShift(props.person);
    },
);

watch(
    () => props.person,
    (person) => {
        if (person) {
            lastPerson.value = person;
            hydrateShift(person);
        }
    },
    { immediate: true },
);

const toggleShiftDay = (id) => {
    if (shiftForm.days.includes(id)) {
        if (shiftForm.days.length === 1) {
            return;
        }
        shiftForm.days = shiftForm.days.filter((day) => day !== id);
        return;
    }
    shiftForm.days = [...shiftForm.days, id].sort((a, b) => a - b);
};

const saveShift = async () => {
    if (!shown.value) {
        return;
    }
    busy.value = 'shift';
    shiftForm.errors = {};
    try {
        const { data } = await axios.post(route('admin.staff.shift', shown.value.id), {
            shift_days: shiftForm.days,
            shift_starts_at: shiftForm.start,
            shift_ends_at: shiftForm.end,
            shift_breaks: shiftForm.breaks,
        });
        toast(data.toast);
        if (data.staff) {
            emit('updated', data.staff);
            hydrateShift(data.staff);
        }
    } catch (error) {
        shiftForm.errors = error.response?.data?.errors || {};
        toast({
            type: 'error',
            title: 'Couldn’t save shift',
            message: error.response?.data?.message || 'Check the days and times and try again.',
        });
    } finally {
        busy.value = '';
    }
};

const ask = (type) => {
    typedConfirm.value = '';
    dialog.value = type;
};

const openRoles = () => {
    roleDraft.ids = currentRoles.value
        .filter((role) => role.id !== 'super_admin')
        .map((role) => role.id);
    roleDraft.super = !!shown.value?.is_super;
    rolesOpen.value = true;
};

const openMessage = () => {
    messageForm.subject = 'A note from Isabi';
    messageForm.body = `Hi {{first_name}}, `;
    messageForm.channels = ['in_app', 'email'];
    messageForm.errors = {};
    messageOpen.value = true;
};

const openAnnounce = () => {
    announceForm.template_id = '';
    announceForm.subject = '';
    announceForm.body = '';
    announceForm.channels = ['in_app', 'email'];
    announceForm.errors = {};
    announceOpen.value = true;
};

const applyTemplate = () => {
    const template = catalog.value.find((item) => String(item.id) === String(announceForm.template_id));
    if (!template) {
        return;
    }
    announceForm.subject = template.subject || '';
    announceForm.body = template.body || '';
    announceForm.channels = (template.channels || []).filter((item) => item === 'in_app' || item === 'email');
    if (!announceForm.channels.length) {
        announceForm.channels = ['in_app', 'email'];
    }
};

const toggleChannel = (form, value) => {
    if (form.channels.includes(value)) {
        if (form.channels.length === 1) {
            return;
        }
        form.channels = form.channels.filter((item) => item !== value);
        return;
    }
    form.channels = [...form.channels, value];
};

const saveRoles = async () => {
    if (!shown.value) {
        return;
    }
    busy.value = 'roles';
    try {
        const { data } = await axios.put(route('admin.staff.roles.sync', shown.value.id), {
            role_ids: roleDraft.ids,
            is_super: roleDraft.super,
        });
        toast(data.toast);
        if (data.staff) {
            emit('updated', { ...shown.value, ...data.staff });
        }
        emit('refresh');
        rolesOpen.value = false;
    } catch (error) {
        toast({
            type: 'error',
            title: 'Couldn’t update roles',
            message: error.response?.data?.message || error.response?.data?.errors?.role?.[0] || 'Try again.',
        });
    } finally {
        busy.value = '';
    }
};

const submitMessage = async () => {
    if (!shown.value) {
        return;
    }
    busy.value = 'message';
    messageForm.errors = {};
    try {
        const { data } = await axios.post(route('admin.staff.message', shown.value.id), {
            subject: messageForm.subject,
            body: messageForm.body,
            channels: messageForm.channels,
        });
        toast(data.toast);
        messageOpen.value = false;
    } catch (error) {
        messageForm.errors = error.response?.data?.errors || {};
        toast({
            type: 'error',
            title: 'Couldn’t send',
            message: error.response?.data?.message || error.response?.data?.errors?.body?.[0] || 'Try again.',
        });
    } finally {
        busy.value = '';
    }
};

const submitAnnounce = async () => {
    if (!shown.value || !announceForm.template_id) {
        return;
    }
    busy.value = 'announce';
    announceForm.errors = {};
    try {
        const { data } = await axios.post(route('admin.staff.announce', shown.value.id), {
            template_id: Number(announceForm.template_id),
            subject: announceForm.subject,
            body: announceForm.body,
            channels: announceForm.channels,
        });
        toast(data.toast);
        announceOpen.value = false;
    } catch (error) {
        announceForm.errors = error.response?.data?.errors || {};
        toast({
            type: 'error',
            title: 'Couldn’t send',
            message: error.response?.data?.message || error.response?.data?.errors?.template_id?.[0] || 'Try again.',
        });
    } finally {
        busy.value = '';
    }
};

const resendInvite = async () => {
    if (!shown.value) {
        return;
    }
    busy.value = 'resend';
    try {
        const { data } = await axios.post(route('admin.staff.resend', shown.value.id));
        toast(data.toast);
        if (data.staff) {
            emit('updated', { ...shown.value, ...data.staff });
        }
    } catch (error) {
        toast({
            type: 'error',
            title: 'Couldn’t resend',
            message: error.response?.data?.message || error.response?.data?.errors?.email?.[0] || 'Try again shortly.',
        });
    } finally {
        busy.value = '';
    }
};

const runDialog = async ({ reason, confirmation }) => {
    if (!shown.value || !dialog.value) {
        return;
    }
    const type = dialog.value;
    busy.value = type;
    const id = shown.value.id;
    try {
        const payload = {
            reason,
            confirmation: type === 'remove' ? typedConfirm.value : confirmation,
        };
        const urls = {
            disable: route('admin.staff.disable', id),
            reinstate: route('admin.staff.reinstate', id),
            revoke: route('admin.staff.revoke', id),
            logout: route('admin.staff.logout', id),
            reset: route('admin.staff.password-reset', id),
            remove: route('admin.staff.destroy', id),
        };
        const { data } = await axios.post(urls[type], payload);
        toast(data.toast);
        if (data.deleted_id) {
            emit('deleted', data.deleted_id);
        } else if (data.staff) {
            emit('updated', { ...shown.value, ...data.staff });
            emit('refresh');
        }
        dialog.value = null;
    } catch (error) {
        toast({
            type: 'error',
            title: 'Couldn’t complete',
            message: error.response?.data?.message
                || error.response?.data?.errors?.confirmation?.[0]
                || error.response?.data?.errors?.reason?.[0]
                || 'Try again.',
        });
    } finally {
        busy.value = '';
    }
};

const initials = (name) =>
    String(name || 'I')
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();

const statusClass = (status) => {
    if (status === 'active') return 'bg-emerald-50 text-emerald-700';
    if (status === 'suspended') return 'bg-red-50 text-red-600';
    return 'bg-pale text-ink/50';
};
</script>

<style>
.admin-pane-enter-active,
.admin-pane-leave-active {
    transition:
        opacity 0.2s cubic-bezier(0.32, 0.72, 0, 1),
        transform 0.22s cubic-bezier(0.32, 0.72, 0, 1);
}
.admin-pane-enter-from,
.admin-pane-leave-to {
    opacity: 0;
    transform: translate3d(0, 8px, 0);
}
</style>
