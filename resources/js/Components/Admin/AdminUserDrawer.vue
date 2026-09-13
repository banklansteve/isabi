<template>
    <AdminDrawer :open="!!person" size="lg" @close="$emit('close')">
        <template #header>
            <div v-if="shown" class="flex items-start gap-3">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-tint text-sm font-bold text-deep">
                    <img v-if="shown.avatar_url" :src="shown.avatar_url" alt="" class="h-full w-full object-cover" />
                    <span v-else>{{ initials(shown.name) }}</span>
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="truncate text-[17px] font-bold tracking-tight text-ink">{{ shown.business_name }}</h2>
                    <p class="mt-0.5 truncate text-[13px] font-medium text-ink/45">
                        {{ [shown.trade, shown.state, shown.joined_short ? `Joined ${shown.joined_short}` : null].filter(Boolean).join(' · ') }}
                    </p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide" :class="planClass(shown)">
                            {{ shown.plan }} plan
                        </span>
                        <span
                            class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                            :class="shown.verified ? 'bg-pale text-ink/50' : 'bg-amber-50 text-amber-700'"
                        >
                            {{ shown.verified ? 'Verified' : 'Unverified' }}
                        </span>
                        <span
                            v-if="shown.suspended"
                            class="rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-red-600"
                        >
                            Suspended
                        </span>
                    </div>
                </div>
                <div ref="menuRoot" class="relative shrink-0">
                    <button
                        type="button"
                        class="tap-target flex h-10 w-10 items-center justify-center rounded-xl text-ink/40 hover:bg-pale hover:text-ink"
                        aria-label="More actions"
                        @click="menu = !menu"
                    >
                        <i class="ti ti-dots text-lg" aria-hidden="true" />
                    </button>
                    <AdminSlideMenu :open="menu">
                        <button type="button" class="menu-item" @click="ask('verify')">
                            {{ shown.verified ? 'Unverify email' : 'Manually verify' }}
                        </button>
                        <button type="button" class="menu-item" @click="ask('edit')">Edit profile</button>
                        <button v-if="isSuper" type="button" class="menu-item" @click="ask('credits')">Adjust credits</button>
                        <button v-if="isSuper" type="button" class="menu-item" @click="ask('plan')">Change plan</button>
                        <button type="button" class="menu-item" @click="ask('logout')">Force logout</button>
                        <button type="button" class="menu-item" @click="ask('password')">Send password reset</button>
                        <button v-if="canManageUsers" type="button" class="menu-item text-red-600" @click="ask('delete')">
                            Delete account
                        </button>
                    </AdminSlideMenu>
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
            <div class="grid grid-cols-4 gap-2">
                <div v-for="stat in stats" :key="stat.label" class="rounded-xl bg-pale px-2 py-3 text-center sm:px-3">
                    <p class="text-[11px] font-semibold text-ink/40">{{ stat.label }}</p>
                    <p class="mt-1 text-[17px] font-bold tabular-nums tracking-tight text-ink">{{ stat.value }}</p>
                </div>
            </div>

            <div
                v-if="panel?.escalation"
                class="rounded-xl bg-violet-50/80 p-3 ring-1 ring-violet-100"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-violet-700">Escalated to Super Admin</p>
                        <p
                            class="mt-1 text-[13px] font-bold"
                            :class="escalationStatusClass(panel.escalation.tone)"
                        >
                            {{ panel.escalation.status }}
                        </p>
                        <p v-if="panel.escalation.note" class="mt-1 text-[12px] font-medium leading-relaxed text-ink/60">
                            {{ panel.escalation.note }}
                        </p>
                        <p v-if="panel.escalation.referred_by" class="mt-1 text-[11px] font-semibold text-ink/40">
                            From {{ panel.escalation.referred_by }}
                        </p>
                    </div>
                    <button
                        v-if="isSuper && panel.escalation.tone !== 'resolved'"
                        type="button"
                        class="shrink-0 rounded-xl bg-base-action px-3 py-2 text-[12px] font-semibold text-white hover:bg-base-hover disabled:opacity-60"
                        :disabled="resolveBusy"
                        @click="resolveOpen = true"
                    >
                        Mark resolved
                    </button>
                </div>
            </div>

            <div class="no-scrollbar flex gap-1 overflow-x-auto rounded-full bg-pale p-1">
                <button
                    v-for="item in tabs"
                    :key="item.id"
                    type="button"
                    class="shrink-0 rounded-full px-3 py-1.5 text-[12px] font-semibold transition-all duration-200 ease-[cubic-bezier(0.32,0.72,0,1)] sm:text-[13px]"
                    :class="tab === item.id ? 'bg-white text-ink shadow-sm' : 'text-ink/40 hover:text-ink'"
                    @click="tab = item.id"
                >
                    {{ item.label }}
                </button>
            </div>

            <div v-if="loading" class="space-y-3" aria-hidden="true">
                <div class="h-4 w-32 animate-pulse rounded bg-pale" />
                <div class="h-16 animate-pulse rounded-xl bg-pale" />
                <div class="h-24 animate-pulse rounded-xl bg-pale" />
            </div>

            <Transition name="admin-pane" mode="out-in">
            <div v-if="!loading && tab === 'overview'" key="overview" class="space-y-4">
                <div v-if="shown.avatar_url" class="overflow-hidden rounded-2xl bg-pale">
                    <img :src="shown.avatar_url" :alt="shown.business_name" class="h-40 w-full object-cover" />
                </div>
                <dl class="grid gap-3 text-[13px] sm:grid-cols-2">
                    <div>
                        <dt class="font-semibold text-ink/40">Email</dt>
                        <dd class="mt-0.5 font-medium text-ink">{{ detail.email }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-ink/40">WhatsApp</dt>
                        <dd class="mt-0.5 font-medium text-ink">{{ detail.whatsapp || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-ink/40">Public slug</dt>
                        <dd class="mt-0.5 font-medium text-ink">{{ detail.slug || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-ink/40">Verification</dt>
                        <dd class="mt-0.5 font-medium text-ink">
                            {{ shown.verified ? (detail.email_verified_at || 'Verified') : 'Unverified' }}
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-semibold text-ink/40">Profile completion</dt>
                        <dd class="mt-1.5">
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-pale">
                                    <div
                                        class="h-full rounded-full bg-base-action"
                                        :style="{ width: `${Math.min(100, detail.profile_completion ?? shown.profile_completion ?? 0)}%` }"
                                    />
                                </div>
                                <span class="text-[12px] font-bold tabular-nums text-ink">
                                    {{ detail.profile_completion ?? shown.profile_completion }}%
                                </span>
                            </div>
                        </dd>
                    </div>
                </dl>
                <p v-if="detail.bio" class="text-[14px] font-medium leading-relaxed text-ink/70">{{ detail.bio }}</p>
                <a
                    v-if="shown.public_url"
                    :href="shown.public_url"
                    target="_blank"
                    rel="noreferrer"
                    class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-base-action hover:text-base-hover"
                >
                    Public page preview
                    <i class="ti ti-external-link text-sm" aria-hidden="true" />
                </a>
            </div>

            <div v-else-if="!loading && tab === 'jobs'" key="jobs" class="space-y-3">
                <p v-if="!panel?.jobs?.length" class="text-sm text-ink/40">No jobs logged.</p>
                <article
                    v-for="job in panel?.jobs || []"
                    :key="job.id"
                    class="rounded-xl border border-ink/[0.06] p-3"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-ink">{{ job.description }}</p>
                            <p class="mt-0.5 text-[12px] font-medium text-ink/40">
                                {{ job.client_name || 'No client' }} · {{ job.worked_on_label }} · {{ job.status }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <button type="button" class="chip" @click="ask('edit-job', job)">Edit</button>
                        <button v-if="!job.flagged" type="button" class="chip" @click="ask('flag-job', job)">Flag</button>
                        <button v-if="!job.hidden" type="button" class="chip" @click="ask('hide-job', job)">Hide</button>
                        <button v-if="!job.removed" type="button" class="chip text-red-600" @click="ask('delete-job', job)">Delete</button>
                    </div>
                </article>
            </div>

            <div v-else-if="!loading && tab === 'reviews'" key="reviews" class="space-y-4">
                <div>
                    <h3 class="text-[13px] font-bold text-ink">Reviews received</h3>
                    <p v-if="!panel?.reviews?.length" class="mt-2 text-sm text-ink/40">None yet.</p>
                    <ul v-else class="mt-2 divide-y divide-ink/[0.06]">
                        <li v-for="review in panel.reviews" :key="review.id" class="py-3">
                            <p class="text-sm font-bold text-ink">{{ review.rating }}★ · {{ review.client || 'Client' }}</p>
                            <p class="mt-0.5 line-clamp-3 text-[13px] text-ink/55">{{ review.comment || 'No comment' }}</p>
                            <div class="mt-2 flex gap-1.5">
                                <button v-if="!review.flagged" type="button" class="chip" @click="ask('flag-review', review)">Flag</button>
                                <button v-if="!review.hidden" type="button" class="chip" @click="ask('hide-review', review)">Hide</button>
                                <button v-if="!review.removed" type="button" class="chip text-red-600" @click="ask('delete-review', review)">Delete</button>
                            </div>
                        </li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-[13px] font-bold text-ink">Request history</h3>
                    <ul v-if="panel?.review_requests?.length" class="mt-2 divide-y divide-ink/[0.06]">
                        <li v-for="row in panel.review_requests" :key="row.id" class="flex justify-between gap-3 py-2.5 text-[13px]">
                            <span class="min-w-0 truncate font-medium text-ink">{{ row.description }}</span>
                            <span class="shrink-0 font-semibold capitalize" :class="requestClass(row.status)">{{ row.status.replace('-', ' ') }}</span>
                        </li>
                    </ul>
                    <p v-else class="mt-2 text-sm text-ink/40">No review links sent.</p>
                </div>
            </div>

            <div v-else-if="!loading && tab === 'credits'" key="credits" class="space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-[15px] font-bold text-ink">
                        Balance: {{ panel?.credits?.balance ?? shown.token_balance }} tokens
                    </p>
                    <button
                        v-if="isSuper"
                        type="button"
                        class="rounded-lg bg-pale px-2.5 py-1.5 text-[11px] font-bold text-deep"
                        @click="ask('credits')"
                    >
                        Adjust
                    </button>
                </div>
                <ul v-if="panel?.credits?.transactions?.length" class="divide-y divide-ink/[0.06]">
                    <li v-for="row in panel.credits.transactions" :key="row.id" class="flex justify-between gap-3 py-2.5">
                        <div class="min-w-0">
                            <p class="truncate text-[13px] font-medium text-ink">{{ row.description }}</p>
                            <p class="text-[11px] text-ink/40">{{ row.when }}</p>
                        </div>
                        <p class="shrink-0 text-sm font-bold tabular-nums" :class="row.type === 'debit' ? 'text-coral' : 'text-ink'">
                            {{ row.type === 'debit' ? '−' : '+' }}{{ row.amount }}
                        </p>
                    </li>
                </ul>
                <p v-else class="text-sm text-ink/40">No movements yet.</p>
                <div v-if="panel?.credits?.purchases?.length">
                    <h3 class="text-[13px] font-bold text-ink">Purchases</h3>
                    <ul class="mt-2 divide-y divide-ink/[0.06]">
                        <li v-for="row in panel.credits.purchases" :key="row.id" class="flex justify-between gap-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-[13px] font-medium text-ink">{{ row.pack_name }}</p>
                                <p class="text-[11px] capitalize text-ink/40">{{ row.status }} · {{ row.when }}</p>
                            </div>
                            <p class="shrink-0 text-sm font-bold tabular-nums text-ink">{{ row.tokens }}</p>
                        </li>
                    </ul>
                </div>
            </div>

            <div v-else-if="!loading" key="activity" class="space-y-5">
                <div>
                    <h3 class="text-[13px] font-bold text-ink">Sign-in history</h3>
                    <ul v-if="panel?.activity?.logins?.length" class="mt-2 divide-y divide-ink/[0.06]">
                        <li v-for="row in panel.activity.logins" :key="row.id" class="py-2.5 text-[13px]">
                            <p class="font-semibold text-ink">{{ row.title }}</p>
                            <p class="text-ink/40">{{ row.when }}<span v-if="row.ip"> · {{ row.ip }}</span></p>
                        </li>
                    </ul>
                    <p v-else class="mt-2 text-sm text-ink/40">No sign-ins recorded.</p>
                </div>
                <div>
                    <h3 class="text-[13px] font-bold text-ink">Admin actions on this account</h3>
                    <ul v-if="panel?.activity?.admin_actions?.length" class="mt-2 divide-y divide-ink/[0.06]">
                        <li v-for="row in panel.activity.admin_actions" :key="row.id" class="py-2.5 text-[13px]">
                            <p class="font-semibold text-ink">{{ row.summary }}</p>
                            <p class="text-ink/40">{{ row.actor || 'System' }} · {{ row.when }}</p>
                        </li>
                    </ul>
                    <p v-else class="mt-2 text-sm text-ink/40">No admin actions yet.</p>
                </div>
            </div>
            </Transition>
        </div>

        <template #footer>
            <div v-if="shown" class="space-y-2">
                <button
                    v-if="canEscalate && !panel?.escalation"
                    type="button"
                    class="w-full rounded-xl border border-violet-200 bg-violet-50 px-3 py-2.5 text-sm font-semibold text-violet-800 transition-colors duration-150 hover:bg-violet-100 disabled:opacity-60"
                    :disabled="escalateBusy"
                    @click="escalateOpen = true"
                >
                    Escalate to Super Admin
                </button>
                <div class="flex gap-2">
                <button
                    type="button"
                    class="flex-1 rounded-xl bg-base-action px-3 py-2.5 text-sm font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-colors duration-150 hover:bg-base-hover"
                    @click="ask('message')"
                >
                    Message
                </button>
                <button
                    v-if="canWarn"
                    type="button"
                    class="flex-1 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm font-semibold text-amber-900 transition-colors duration-150 hover:bg-amber-100"
                    @click="warnOpen = true"
                >
                    Warn
                </button>
                <button
                    v-if="canImpersonate"
                    type="button"
                    class="flex-1 rounded-xl border border-base-action/35 bg-white px-3 py-2.5 text-sm font-semibold text-base-action transition-colors duration-150 hover:bg-tint"
                    @click="ask('impersonate')"
                >
                    View as
                </button>
                <button
                    type="button"
                    class="flex-1 rounded-xl border px-3 py-2.5 text-sm font-semibold transition-colors duration-150"
                    :class="shown.suspended ? 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' : 'border-red-200 text-red-600 hover:bg-red-50'"
                    @click="ask(shown.suspended ? 'reinstate' : 'suspend')"
                >
                    {{ shown.suspended ? 'Reinstate' : 'Suspend' }}
                </button>
                </div>
            </div>
        </template>
    </AdminDrawer>

    <EscalateToSuperDialog
        :open="escalateOpen"
        title="Escalate this profile to Super Admin?"
        description="Include what you tried and why you need a decision upstairs."
        :processing="escalateBusy"
        @close="escalateOpen = false"
        @confirm="submitEscalate"
    />

    <AdminConfirmDialog
        :open="resolveOpen"
        title="Mark this escalation resolved?"
        description="Closes the Super Admin queue item for this profile."
        confirm-label="Mark resolved"
        :processing="resolveBusy"
        @close="resolveOpen = false"
        @confirm="submitResolveEscalation"
    />

    <AdminConfirmDialog
        :open="!!dialog"
        :title="dialogMeta.title"
        :description="dialogMeta.description"
        :confirm-label="dialogMeta.confirmLabel"
        :tone="dialogMeta.tone"
        :require-reason="dialogMeta.requireReason !== false"
        :confirm-phrase="confirmPhraseForDialog"
        :processing="busy"
        @close="closeDialog"
        @confirm="runDialog"
    >
        <template v-if="dialog === 'message'">
            <input
                v-model="compose.subject"
                type="text"
                placeholder="Subject"
                class="mt-4 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
            />
            <textarea
                v-model="compose.body"
                rows="4"
                placeholder="Keep it specific…"
                class="mt-2 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
            />
        </template>
        <template v-else-if="dialog === 'credits'">
            <div class="mt-4 grid grid-cols-2 gap-2">
                <select v-model="creditForm.direction" class="rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium">
                    <option value="credit">Add tokens</option>
                    <option value="debit">Remove tokens</option>
                </select>
                <input v-model.number="creditForm.amount" type="number" min="1" class="rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium" />
            </div>
        </template>
        <template v-else-if="dialog === 'plan'">
            <select v-model="planForm.plan" class="mt-4 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium">
                <option value="free">Free</option>
                <option value="payg">Pay-as-you-go</option>
                <option value="annual">Annual</option>
            </select>
            <input
                v-if="planForm.plan === 'annual'"
                v-model="planForm.annual_expires_at"
                type="date"
                class="mt-2 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium"
            />
        </template>
        <template v-else-if="dialog === 'edit' && detail">
            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                <input v-model="profileForm.first_name" placeholder="First name" class="field" />
                <input v-model="profileForm.last_name" placeholder="Last name" class="field" />
                <input v-model="profileForm.business_name" placeholder="Business" class="field sm:col-span-2" />
                <input v-model="profileForm.trade" placeholder="Trade" class="field" />
                <input v-model="profileForm.whatsapp" placeholder="WhatsApp" class="field" />
                <input v-model="profileForm.state" placeholder="State" class="field" />
                <input v-model="profileForm.lga" placeholder="LGA" class="field" />
                <input v-model="profileForm.slug" placeholder="Public slug" class="field sm:col-span-2" />
                <textarea v-model="profileForm.bio" rows="3" placeholder="Bio" class="field sm:col-span-2" />
            </div>
        </template>
        <template v-else-if="dialog === 'edit-job' && editJob">
            <input v-model="editJob.description" class="field mt-4" />
            <input v-model="editJob.client_name" placeholder="Client" class="field mt-2" />
            <input v-model="editJob.worked_on" type="date" class="field mt-2" />
        </template>
    </AdminConfirmDialog>

    <OpsWarnUserDialog :open="warnOpen" :user="shown" @close="warnOpen = false" />
</template>

<script setup>
import AdminConfirmDialog from '@/Components/Admin/AdminConfirmDialog.vue';
import AdminDrawer from '@/Components/Admin/AdminDrawer.vue';
import AdminSlideMenu from '@/Components/Admin/AdminSlideMenu.vue';
import EscalateToSuperDialog from '@/Components/Admin/EscalateToSuperDialog.vue';
import OpsWarnUserDialog from '@/Components/Admin/OpsWarnUserDialog.vue';
import { toast } from '@/utils/adminRange';
import { escalationStatusClass } from '@/utils/opsStatus';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';

const props = defineProps({
    person: { type: Object, default: null },
    panel: { type: Object, default: null },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'updated', 'deleted', 'refresh']);

const page = usePage();
const isSuper = computed(() => !!page.props.auth?.user?.is_super_admin);
const canImpersonate = computed(
    () => isSuper.value || (page.props.auth?.user?.abilities || []).includes('admin.users.impersonate'),
);
const canManageUsers = computed(
    () => isSuper.value || (page.props.auth?.user?.abilities || []).includes('admin.users.manage'),
);
const canWarn = computed(() => {
    const keys = page.props.auth?.user?.abilities || [];

    return isSuper.value
        || keys.includes('admin.ops_messages.send')
        || keys.includes('admin.users.manage')
        || keys.includes('admin.messaging.manage');
});
const canEscalate = computed(() => !!props.panel?.can_escalate && !isSuper.value);
const tab = ref('overview');
const menu = ref(false);
const menuRoot = ref(null);
const dialog = ref(null);
const target = ref(null);
const busy = ref(false);
const escalateOpen = ref(false);
const escalateBusy = ref(false);
const warnOpen = ref(false);
const resolveOpen = ref(false);
const resolveBusy = ref(false);
const editJob = ref(null);
const lastPerson = ref(null);

const shown = computed(() => props.person || lastPerson.value);

const compose = reactive({ subject: '', body: '' });
const creditForm = reactive({ direction: 'credit', amount: 5 });
const planForm = reactive({ plan: 'free', annual_expires_at: '' });
const profileForm = reactive({
    first_name: '',
    last_name: '',
    business_name: '',
    trade: '',
    whatsapp: '',
    state: '',
    lga: '',
    slug: '',
    bio: '',
});

const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'jobs', label: 'Job logs' },
    { id: 'reviews', label: 'Reviews' },
    { id: 'credits', label: 'Credits' },
    { id: 'activity', label: 'Activity' },
];

const detail = computed(() => props.panel?.user || shown.value || {});

const stats = computed(() => {
    const user = detail.value;
    return [
        { label: 'Jobs', value: user.jobs ?? '—' },
        { label: 'Reviews', value: user.reviews ?? '—' },
        { label: 'Credits', value: user.token_balance ?? user.credits?.balance ?? '—' },
        { label: 'Revenue', value: user.revenue_label || '₦0' },
    ];
});

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
        menu.value = false;
        dialog.value = null;
        warnOpen.value = false;
        editJob.value = null;
    },
);

watch(
    () => props.person,
    (person) => {
        if (person) {
            lastPerson.value = person;
        }
    },
);

watch(
    () => props.panel?.user,
    (user) => {
        if (!user) return;
        Object.assign(profileForm, {
            first_name: user.first_name || '',
            last_name: user.last_name || '',
            business_name: user.business_name || '',
            trade: user.trade || '',
            whatsapp: user.whatsapp || '',
            state: user.state || '',
            lga: user.lga || '',
            slug: user.slug || '',
            bio: user.bio || '',
        });
        const key = user.plan_key || '';
        planForm.plan = key === 'annual' ? 'annual' : key === 'payg' ? 'payg' : 'free';
        planForm.annual_expires_at = user.annual_expires_at || '';
    },
);

const dialogMeta = computed(() => {
    const map = {
        message: {
            title: 'Message this artisan',
            description: 'Sends in-app and email to this one account. Your reason is logged.',
            confirmLabel: 'Send',
            requireReason: true,
        },
        suspend: { title: 'Suspend account', description: 'They will be signed out and blocked from signing in.', confirmLabel: 'Suspend', tone: 'danger' },
        reinstate: { title: 'Reinstate account', description: 'They can sign in again.', confirmLabel: 'Reinstate' },
        verify: { title: shown.value?.verified ? 'Clear verification' : 'Manually verify', description: 'This is logged on the account.', confirmLabel: 'Save' },
        impersonate: { title: 'View as this artisan', description: 'You’ll see Kraftrack exactly as they do. Leave from the banner at the top.', confirmLabel: 'View as' },
        logout: { title: 'Force logout', description: 'Clears every active session on this account.', confirmLabel: 'Sign them out' },
        password: { title: 'Send password reset', description: 'Emails a reset link to this artisan.', confirmLabel: 'Send link' },
        credits: { title: 'Adjust credits', description: 'Add or remove tokens. Always logged.', confirmLabel: 'Update balance' },
        plan: { title: 'Change plan', description: 'Upgrade, downgrade, or extend annual access.', confirmLabel: 'Update plan' },
        edit: { title: 'Edit profile', description: 'Support edits on their behalf.', confirmLabel: 'Save profile' },
        delete: {
            title: 'Delete account',
            description: isSuper.value
                ? 'Soft-delete first so mistakes can be recovered.'
                : 'This sends a delete request to Super Admin. The account stays until they approve.',
            confirmLabel: 'Delete',
            tone: 'danger',
        },
        'flag-job': { title: 'Flag this job', description: 'Marks it for moderation.', confirmLabel: 'Flag' },
        'hide-job': { title: 'Hide this job', description: 'Hides it from the public page immediately.', confirmLabel: 'Hide', tone: 'danger' },
        'delete-job': {
            title: 'Delete this job',
            description: isSuper.value
                ? 'Soft-removes it from the public page.'
                : 'Super Admin must approve before this job is deleted.',
            confirmLabel: 'Delete',
            tone: 'danger',
        },
        'flag-review': { title: 'Flag this review', description: 'Policy or authenticity concern.', confirmLabel: 'Flag' },
        'hide-review': { title: 'Hide this review', description: 'Hides it from the public page immediately.', confirmLabel: 'Hide', tone: 'danger' },
        'delete-review': {
            title: 'Delete this review',
            description: isSuper.value
                ? 'Soft-removes it from the public page.'
                : 'Super Admin must approve before this review is deleted.',
            confirmLabel: 'Delete',
            tone: 'danger',
        },
        'edit-job': { title: 'Edit job', description: 'Support correction. Logged with your reason.', confirmLabel: 'Save job' },
    };
    return map[dialog.value] || { title: 'Confirm', description: '', confirmLabel: 'Confirm' };
});

const confirmPhraseForDialog = computed(() => {
    if (dialog.value === 'delete') {
        return shown.value?.business_name || '';
    }
    if (dialog.value === 'delete-job' || dialog.value === 'delete-review') {
        return 'DELETE';
    }
    return '';
});

const closeDialog = () => {
    dialog.value = null;
    editJob.value = null;
};

const ask = (type, item = null) => {
    menu.value = false;
    target.value = item;
    if (type === 'message') {
        compose.subject = `A note from Kraftrack`;
        compose.body = `Hi {{first_name}}, `;
    }
    if (type === 'edit-job' && item) {
        editJob.value = { ...item };
    }
    dialog.value = type;
};

const onDocClick = (event) => {
    if (menu.value && menuRoot.value && !menuRoot.value.contains(event.target)) {
        menu.value = false;
    }
};

onMounted(() => document.addEventListener('click', onDocClick));
onUnmounted(() => document.removeEventListener('click', onDocClick));

const jsonHeaders = { headers: { Accept: 'application/json' } };

const submitEscalate = async ({ note }) => {
    if (!shown.value?.id) {
        return;
    }

    escalateBusy.value = true;
    try {
        const { data } = await axios.post(route('admin.escalations.store'), {
            subject_type: 'user',
            subject_uid: String(shown.value.id),
            note,
        }, jsonHeaders);
        escalateOpen.value = false;
        toast(data.toast || { type: 'success', title: 'Escalated', message: 'Super Admin has been notified.' });
        emit('refresh');
    } catch (error) {
        toast({
            type: 'error',
            title: 'Couldn’t escalate',
            message: error?.response?.data?.errors?.note?.[0] || 'Try that again in a moment.',
        });
    } finally {
        escalateBusy.value = false;
    }
};

const submitResolveEscalation = async ({ reason }) => {
    const escalationId = props.panel?.escalation?.id;
    if (!escalationId) {
        return;
    }

    resolveBusy.value = true;
    try {
        const { data } = await axios.post(route('admin.escalations.complete', escalationId), { note: reason }, jsonHeaders);
        resolveOpen.value = false;
        toast(data.toast || { type: 'success', title: 'Resolved', message: 'Escalation closed.' });
        emit('refresh');
    } catch {
        toast({ type: 'error', title: 'Couldn’t resolve', message: 'Try that again in a moment.' });
    } finally {
        resolveBusy.value = false;
    }
};

const runDialog = async ({ reason, confirmation }) => {
    if (!shown.value || !dialog.value) return;
    busy.value = true;
    const id = shown.value.id;
    try {
        if (dialog.value === 'message') {
            const { data } = await axios.post(route('admin.messaging.store'), {
                audience: 'users',
                title: compose.subject,
                subject: compose.subject,
                body: compose.body,
                channels: ['in_app', 'email'],
                segment: { user_id: id },
                action: 'send',
                reason,
            });
            toast(data.toast);
        } else if (dialog.value === 'impersonate') {
            router.post(route('admin.users.impersonate', id), { reason });
            return;
        } else if (dialog.value === 'suspend' || dialog.value === 'reinstate' || dialog.value === 'logout' || dialog.value === 'password') {
            const routes = {
                suspend: route('admin.users.suspend', id),
                reinstate: route('admin.users.reinstate', id),
                logout: route('admin.users.logout', id),
                password: route('admin.users.password-reset', id),
            };
            const { data } = await axios.post(routes[dialog.value], { reason });
            toast(data.toast);
            if (data.user) emit('updated', data.user);
            emit('refresh');
        } else if (dialog.value === 'verify') {
            const url = shown.value.verified ? route('admin.users.unverify', id) : route('admin.users.verify', id);
            const { data } = await axios.post(url, { reason });
            toast(data.toast);
            if (data.user) emit('updated', data.user);
            emit('refresh');
        } else if (dialog.value === 'credits') {
            const { data } = await axios.post(route('admin.users.credits', id), { ...creditForm, reason });
            toast(data.toast);
            if (data.panel?.user) emit('updated', data.panel.user);
            emit('refresh');
        } else if (dialog.value === 'plan') {
            const { data } = await axios.post(route('admin.users.plan', id), { ...planForm, reason });
            toast(data.toast);
            if (data.panel?.user) emit('updated', data.panel.user);
            emit('refresh');
        } else if (dialog.value === 'edit') {
            const { data } = await axios.patch(route('admin.users.update', id), {
                ...profileForm,
                state: profileForm.state || null,
                lga: profileForm.lga || null,
                reason,
            });
            toast(data.toast);
            if (data.panel?.user) emit('updated', data.panel.user);
            emit('refresh');
        } else if (dialog.value === 'delete') {
            const { data } = await axios.post(route('admin.users.destroy', id), { reason, confirmation });
            toast(data.toast);
            emit('deleted', id);
        } else if (dialog.value === 'flag-job' && target.value) {
            const { data } = await axios.post(route('admin.jobs.flag', target.value.uid), { reason });
            toast(data.toast);
            emit('refresh');
        } else if (dialog.value === 'hide-job' && target.value) {
            const { data } = await axios.post(route('admin.jobs.hide', target.value.uid), { reason });
            toast(data.toast);
            emit('refresh');
        } else if (dialog.value === 'delete-job' && target.value) {
            const { data } = await axios.post(route('admin.jobs.remove', target.value.uid), { reason });
            toast(data.toast);
            emit('refresh');
        } else if (dialog.value === 'edit-job' && editJob.value) {
            const { data } = await axios.patch(route('admin.jobs.update', editJob.value.uid), {
                reason,
                description: editJob.value.description,
                client_name: editJob.value.client_name,
                worked_on: editJob.value.worked_on,
            });
            toast(data.toast);
            emit('refresh');
        } else if (dialog.value === 'flag-review' && target.value) {
            const { data } = await axios.post(route('admin.reviews.flag', target.value.uid || target.value.id), { reason });
            toast(data.toast);
            emit('refresh');
        } else if (dialog.value === 'hide-review' && target.value) {
            const { data } = await axios.post(route('admin.reviews.hide', target.value.uid || target.value.id), { reason });
            toast(data.toast);
            emit('refresh');
        } else if (dialog.value === 'delete-review' && target.value) {
            const { data } = await axios.post(route('admin.reviews.remove', target.value.uid || target.value.id), { reason });
            toast(data.toast);
            emit('refresh');
        }
        dialog.value = null;
        editJob.value = null;
        menu.value = false;
    } catch (error) {
        const message = error.response?.data?.message || error.response?.data?.toast?.message || 'Try that again in a moment.';
        toast({ type: 'error', title: 'Couldn’t save', message });
    } finally {
        busy.value = false;
    }
};

const initials = (name) =>
    String(name || 'I')
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();

const planClass = (person) => {
    if (person.plan === 'Annual') return 'bg-emerald-50 text-emerald-700';
    if (person.plan === 'Pay-as-you-go') return 'bg-tint text-deep';
    return 'bg-pale text-ink/50';
};

const requestClass = (status) => {
    if (status === 'completed') return 'text-emerald-700';
    if (status === 'no-response') return 'text-coral';
    return 'text-ink/45';
};
</script>

<style scoped>
.menu-item {
    @apply block w-full px-3 py-2 text-left text-[13px] font-semibold text-ink/70 hover:bg-pale;
}
.chip {
    @apply rounded-lg bg-pale px-2.5 py-1 text-[11px] font-bold text-ink/55 transition-colors hover:bg-tint;
}
.field {
    @apply w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>

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
