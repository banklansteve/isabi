<template>
    <Head title="Settings" />

    <AdminChrome title="Settings" eyebrow="Global configuration" />

    <!-- Maintenance tab — risk-themed control panel -->
    <div v-if="tab === 'maintenance'" class="space-y-4">
        <section
            class="overflow-hidden rounded-2xl shadow-premium ring-1 transition-colors duration-200"
            :class="
                maintenanceState.enabled
                    ? 'bg-red-50/80 ring-red-200/80'
                    : 'bg-white ring-ink/[0.05]'
            "
        >
            <div
                class="flex items-start gap-3 border-b px-4 py-3.5 sm:px-5"
                :class="
                    maintenanceState.enabled
                        ? 'border-red-200/70 bg-red-600 text-white'
                        : 'border-ink/[0.06] bg-pale/60'
                "
            >
                <span
                    class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                    :class="
                        maintenanceState.enabled
                            ? 'bg-white/15 text-white'
                            : 'bg-red-50 text-red-600 ring-1 ring-red-100'
                    "
                >
                    <i
                        :class="maintenanceState.enabled ? 'ti ti-alert-octagon' : 'ti ti-alert-triangle'"
                        class="text-lg"
                        aria-hidden="true"
                    />
                </span>
                <div class="min-w-0 flex-1">
                    <p
                        class="text-[11px] font-bold uppercase tracking-[0.14em]"
                        :class="maintenanceState.enabled ? 'text-white/70' : 'text-red-600/80'"
                    >
                        High-impact control
                    </p>
                    <h2
                        class="mt-0.5 text-[15px] font-bold tracking-tight"
                        :class="maintenanceState.enabled ? 'text-white' : 'text-ink'"
                    >
                        {{ maintenanceState.enabled ? 'Site is in maintenance' : 'Site maintenance' }}
                    </h2>
                    <p
                        class="mt-1 text-[13px] font-medium leading-relaxed"
                        :class="maintenanceState.enabled ? 'text-white/75' : 'text-ink/50'"
                    >
                        <template v-if="maintenanceState.enabled">
                            Artisans and guests are locked out of every public page. Super Admin and ops
                            still have access. {{ maintenanceMeta || '' }}
                        </template>
                        <template v-else>
                            Putting the site offline blocks artisans and guests immediately. Staff keep
                            working. Sessions are preserved — people resume where they left off when you
                            go live again.
                        </template>
                    </p>
                </div>
            </div>

            <div class="space-y-5 p-4 sm:p-5">
                <div>
                    <p class="text-[12px] font-semibold text-ink/45">Site status</p>
                    <div
                        class="mt-2 inline-flex w-full max-w-sm overflow-hidden rounded-xl border border-ink/10 bg-pale p-1 sm:w-auto"
                        role="group"
                        aria-label="Maintenance mode"
                    >
                        <button
                            type="button"
                            class="tap-target flex flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-bold transition-colors duration-150 sm:min-w-[8.5rem]"
                            :class="
                                !maintenanceState.enabled
                                    ? 'bg-emerald-600 text-white shadow-sm'
                                    : 'bg-transparent text-ink/45 hover:bg-white/70 hover:text-ink'
                            "
                            :aria-pressed="!maintenanceState.enabled"
                            :disabled="toggleProcessing || !maintenanceState.enabled"
                            @click="maintenanceState.enabled && (disableOpen = true)"
                        >
                            <i class="ti ti-circle-check text-base" aria-hidden="true" />
                            Live
                        </button>
                        <button
                            type="button"
                            class="tap-target flex flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-bold transition-colors duration-150 sm:min-w-[8.5rem]"
                            :class="
                                maintenanceState.enabled
                                    ? 'bg-red-600 text-white shadow-sm'
                                    : 'bg-transparent text-ink/45 hover:bg-white/70 hover:text-ink'
                            "
                            :aria-pressed="maintenanceState.enabled"
                            :disabled="toggleProcessing || maintenanceState.enabled"
                            @click="!maintenanceState.enabled && (enableOpen = true)"
                        >
                            <i class="ti ti-player-pause text-base" aria-hidden="true" />
                            Offline
                        </button>
                    </div>
                    <p class="mt-2 text-[12px] font-medium text-ink/40">
                        {{
                            maintenanceState.enabled
                                ? 'Select Live to restore public access.'
                                : 'Select Offline to open the safety checks before locking the site.'
                        }}
                    </p>
                </div>

                <ul class="grid gap-2 sm:grid-cols-2">
                    <li
                        v-for="item in maintenanceImpact"
                        :key="item.label"
                        class="flex items-start gap-2.5 rounded-xl bg-white/80 px-3 py-2.5 ring-1 ring-ink/[0.05]"
                        :class="maintenanceState.enabled ? 'ring-red-100' : ''"
                    >
                        <i
                            :class="item.icon"
                            class="mt-0.5 shrink-0 text-base"
                            :style="{ color: item.tone }"
                            aria-hidden="true"
                        />
                        <span>
                            <span class="block text-[12px] font-bold text-ink">{{ item.label }}</span>
                            <span class="mt-0.5 block text-[11px] font-medium leading-snug text-ink/45">
                                {{ item.hint }}
                            </span>
                        </span>
                    </li>
                </ul>
            </div>
        </section>

        <section
            v-if="maintenanceState.enabled && maintenanceState.bypass_url"
            class="rounded-2xl border border-red-100 bg-white p-4 shadow-premium sm:p-5"
        >
            <div class="flex items-start gap-3">
                <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600 ring-1 ring-red-100"
                >
                    <i class="ti ti-key text-lg" aria-hidden="true" />
                </span>
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-bold text-ink">Public-page bypass</h3>
                    <p class="mt-1 text-[13px] font-medium leading-relaxed text-ink/45">
                        Secret Super Admin link to preview artisan and guest pages while everyone else
                        stays on the maintenance screen. Treat it like a password.
                    </p>
                </div>
            </div>
            <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-stretch">
                <code
                    class="min-w-0 flex-1 overflow-x-auto rounded-xl border border-red-100 bg-red-50/50 px-3.5 py-2.5 font-mono text-[12px] font-medium text-ink/70"
                >
                    {{ maintenanceState.bypass_url }}
                </code>
                <FormButton
                    type="button"
                    variant="secondary"
                    class="sm:shrink-0"
                    :label="copied ? 'Copied' : 'Copy link'"
                    :icon-left="copied ? 'ti ti-check' : 'ti ti-copy'"
                    @click="copyBypass"
                />
            </div>
            <div class="mt-3">
                <FormButton
                    type="button"
                    variant="secondary"
                    label="Regenerate secret"
                    icon-left="ti ti-refresh"
                    :loading="regenProcessing"
                    loading-label="Refreshing…"
                    :disabled="regenProcessing"
                    @click="regenerateSecret"
                />
            </div>
        </section>

        <AdminConfirmDialog
            :open="enableOpen"
            title="Put the site offline?"
            description="This locks artisans and guests out of Kraftrack until you turn maintenance off. Confirm the risks below."
            confirm-label="Put site offline"
            tone="danger"
            :require-reason="false"
            :processing="toggleProcessing"
            @close="enableOpen = false"
            @confirm="confirmEnable"
        >
            <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-3.5 py-3">
                <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-red-700">
                    What will happen
                </p>
                <ul class="mt-2 space-y-1.5 text-[13px] font-medium leading-snug text-red-900/80">
                    <li class="flex gap-2">
                        <i class="ti ti-x mt-0.5 shrink-0 text-red-600" aria-hidden="true" />
                        Public profiles, reviews, quotes, login, and dashboards stop for non-staff.
                    </li>
                    <li class="flex gap-2">
                        <i class="ti ti-check mt-0.5 shrink-0 text-red-600" aria-hidden="true" />
                        Super Admin and ops keep full access; sessions are not wiped.
                    </li>
                    <li class="flex gap-2">
                        <i class="ti ti-alert-triangle mt-0.5 shrink-0 text-red-600" aria-hidden="true" />
                        Do this only after a database backup or host snapshot.
                    </li>
                </ul>
            </div>

            <label
                class="mt-4 flex cursor-pointer items-start gap-3 rounded-xl border border-red-200 bg-white px-3.5 py-3 ring-1 ring-red-100"
            >
                <input
                    v-model="backupConfirmed"
                    type="checkbox"
                    class="mt-0.5 h-4 w-4 rounded border-red-300 text-red-600 focus:ring-red-200"
                />
                <span class="text-[13px] font-semibold leading-snug text-ink/80">
                    I confirm a database backup or snapshot has been taken.
                    <span class="block mt-0.5 text-[12px] font-medium text-ink/45">
                        Required before the site can go offline.
                    </span>
                </span>
            </label>
            <label class="mt-3 block">
                <span class="text-[12px] font-semibold text-ink/50">Note (optional)</span>
                <textarea
                    v-model="enableNote"
                    rows="2"
                    maxlength="500"
                    placeholder="e.g. Pre-deploy snapshot on DigitalOcean"
                    class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-red-400 focus:ring-4 focus:ring-red-100"
                />
            </label>
            <p v-if="enableError" class="mt-2 text-[13px] font-semibold text-red-700">
                {{ enableError }}
            </p>
        </AdminConfirmDialog>

        <AdminConfirmDialog
            :open="disableOpen"
            title="Bring the site back online?"
            description="Artisans and guests regain access immediately. Existing sessions continue from where they left off."
            confirm-label="Go live"
            :require-reason="false"
            :processing="toggleProcessing"
            @close="disableOpen = false"
            @confirm="confirmDisable"
        />
    </div>

    <form v-else class="space-y-4" @submit.prevent="save">
        <section
            v-if="tab === 'session'"
            class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5"
        >
            <h2 class="text-sm font-bold text-ink">Login session duration</h2>
            <p class="mt-1 text-[13px] font-medium text-ink/45">
                How long each kind of account stays signed in after their last activity. New values apply to the next request — including people already signed in.
            </p>
        </section>

        <p
            v-if="!visibleRows.length"
            class="rounded-2xl bg-white p-8 text-center text-sm font-medium text-ink/40 shadow-premium ring-1 ring-ink/[0.05]"
        >
            Nothing in this section yet.
        </p>
        <div
            v-for="row in visibleRows"
            :key="row.key"
            class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5"
        >
            <label class="block">
                <span class="text-sm font-bold text-ink">{{ row.label }}</span>
                <span v-if="row.description" class="mt-0.5 block text-[13px] font-medium text-ink/45">
                    {{ row.description }}
                </span>

                <input
                    v-if="row.type === 'boolean'"
                    v-model="form.settings[row.key]"
                    type="checkbox"
                    true-value="1"
                    false-value="0"
                    class="mt-3 h-5 w-5 rounded border-ink/20 text-base-action focus:ring-base/20"
                />
                <textarea
                    v-else-if="row.type === 'json'"
                    v-model="form.settings[row.key]"
                    rows="6"
                    class="mt-3 w-full rounded-xl border border-ink/10 px-3.5 py-2.5 font-mono text-[13px] font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                />
                <div v-else-if="isLifetimeRow(row.key)" class="mt-3">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <input
                            v-model.number="lifetimeAmounts[row.key]"
                            type="number"
                            min="1"
                            inputmode="numeric"
                            class="w-full rounded-xl border border-ink/10 px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15 sm:max-w-[8.5rem]"
                            @input="syncLifetime(row.key)"
                        />
                        <select
                            v-model="lifetimeUnits[row.key]"
                            class="w-full rounded-xl border border-ink/10 bg-white px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15 sm:max-w-[10rem]"
                            @change="syncLifetime(row.key)"
                        >
                            <option value="minutes">Minutes</option>
                            <option value="hours">Hours</option>
                            <option value="days">Days</option>
                        </select>
                    </div>
                    <p class="mt-2 text-[13px] font-medium text-ink/45">
                        {{ lifetimeHelp(row.key) }}
                    </p>
                </div>
                <div
                    v-else-if="row.readonly"
                    class="mt-3 rounded-xl border border-ink/10 bg-pale px-3.5 py-2.5 text-sm font-medium text-ink/70"
                >
                    {{ form.settings[row.key] || '—' }}
                </div>
                <input
                    v-else-if="row.type === 'integer'"
                    v-model="form.settings[row.key]"
                    type="number"
                    class="mt-3 w-full rounded-xl border border-ink/10 px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                />
                <input
                    v-else
                    v-model="form.settings[row.key]"
                    :type="isSecret(row.key) ? 'password' : 'text'"
                    autocomplete="off"
                    class="mt-3 w-full rounded-xl border border-ink/10 px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                />
            </label>
            <p v-if="row.readonly" class="mt-2 text-[12px] font-semibold text-ink/40">
                Read-only — change this in environment / deploy config.
            </p>
            <p v-if="fieldError(row.key)" class="mt-2 text-[13px] font-semibold text-coral-deep">
                {{ fieldError(row.key) }}
            </p>
        </div>

        <FormButton
            v-if="editableVisibleRows.length"
            type="submit"
            variant="primary"
            label="Save settings"
            loading-label="Saving…"
            :loading="processing"
            :disabled="processing"
        />
    </form>
</template>

<script setup>
import { useAdminTabs } from '@/Composables/useAdminTabs';
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminConfirmDialog from '@/Components/Admin/AdminConfirmDialog.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import { copyToClipboard } from '@/utils/clipboard';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
    maintenance: {
        type: Object,
        default: () => ({
            enabled: false,
            enabled_at: null,
            enabled_by: null,
            bypass_url: null,
            bypass_token: null,
        }),
    },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();

const tabGroups = {
    general: ['general', 'mail', 'profiles'],
    features: ['features'],
    ops: ['ops'],
    payments: ['payments'],
    session: ['session'],
    slugs: ['slugs'],
    maintenance: [],
};

const { tab } = useAdminTabs({ tab: 'general' });

const visibleRows = computed(() => {
    if (tab.value === 'maintenance') {
        return [];
    }
    const groups = tabGroups[tab.value] || [tab.value];
    return groups.flatMap((group) => props.settings[group] || []);
});

const editableVisibleRows = computed(() => visibleRows.value.filter((row) => !row.readonly));

const serialize = (row) => {
    if (row.type === 'json') {
        if (Array.isArray(row.value)) return row.value.join('\n');
        if (row.value && typeof row.value === 'object') return JSON.stringify(row.value, null, 2);
        return row.value ?? '';
    }
    if (row.type === 'boolean') {
        return row.value ? '1' : '0';
    }
    return row.value ?? '';
};

const initial = {};
Object.values(props.settings || {}).forEach((group) => {
    (group || []).forEach((row) => {
        initial[row.key] = serialize(row);
    });
});

const form = reactive({ settings: initial });
const processing = ref(false);
const toggleProcessing = ref(false);
const regenProcessing = ref(false);
const enableOpen = ref(false);
const disableOpen = ref(false);
const backupConfirmed = ref(false);
const enableNote = ref('');
const enableError = ref('');
const copied = ref(false);

const maintenanceState = computed(() => props.maintenance || {});

const maintenanceImpact = [
    {
        label: 'Artisans & guests blocked',
        hint: 'Profiles, reviews, quotes, login, dashboards',
        icon: 'ti ti-lock',
        tone: '#DC2626',
    },
    {
        label: 'Staff stay online',
        hint: 'Super Admin and ops keep full access',
        icon: 'ti ti-shield-check',
        tone: '#059669',
    },
    {
        label: 'No session wipe',
        hint: 'People continue where they left off',
        icon: 'ti ti-database',
        tone: '#1A4FB5',
    },
    {
        label: 'Backup required',
        hint: 'Confirm a snapshot before going offline',
        icon: 'ti ti-cloud-lock',
        tone: '#DC2626',
    },
];

const maintenanceMeta = computed(() => {
    const m = maintenanceState.value;
    if (!m.enabled || !m.enabled_at) {
        return null;
    }
    const when = new Date(m.enabled_at);
    const label = Number.isNaN(when.getTime())
        ? m.enabled_at
        : when.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
    const who = m.enabled_by?.name ? ` by ${m.enabled_by.name}` : '';
    return `Enabled ${label}${who}.`;
});

watch(enableOpen, (open) => {
    if (open) {
        backupConfirmed.value = false;
        enableNote.value = '';
        enableError.value = '';
    }
});

const confirmEnable = () => {
    if (!backupConfirmed.value) {
        enableError.value = 'Confirm that a backup or snapshot has been taken.';
        return;
    }
    enableError.value = '';
    toggleProcessing.value = true;
    router.post(
        route('admin.maintenance.enable'),
        {
            backup_confirmed: true,
            note: enableNote.value || null,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                toggleProcessing.value = false;
            },
            onSuccess: () => {
                enableOpen.value = false;
            },
            onError: (errors) => {
                enableError.value =
                    errors.backup_confirmed || errors.maintenance || 'Could not enable maintenance.';
            },
        },
    );
};

const confirmDisable = () => {
    toggleProcessing.value = true;
    router.post(
        route('admin.maintenance.disable'),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                toggleProcessing.value = false;
            },
            onSuccess: () => {
                disableOpen.value = false;
            },
        },
    );
};

const regenerateSecret = () => {
    regenProcessing.value = true;
    router.post(
        route('admin.maintenance.regenerate'),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                regenProcessing.value = false;
            },
        },
    );
};

const copyBypass = async () => {
    const url = maintenanceState.value.bypass_url;
    if (!url) {
        return;
    }
    const ok = await copyToClipboard(url);
    if (ok) {
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    }
};

const minutesToDuration = (minutes) => {
    const value = Number(minutes) || 0;

    if (value >= 1440 && value % 1440 === 0) {
        return { amount: value / 1440, unit: 'days' };
    }

    if (value >= 60 && value % 60 === 0) {
        return { amount: value / 60, unit: 'hours' };
    }

    return { amount: value, unit: 'minutes' };
};

const durationToMinutes = (amount, unit) => {
    const n = Number(amount) || 0;

    if (unit === 'days') {
        return Math.round(n * 1440);
    }

    if (unit === 'hours') {
        return Math.round(n * 60);
    }

    return Math.round(n);
};

const lifetimeAmounts = reactive({});
const lifetimeUnits = reactive({});

Object.keys(form.settings).forEach((key) => {
    if (!key.startsWith('session.lifetime_')) {
        return;
    }

    const duration = minutesToDuration(form.settings[key]);
    lifetimeAmounts[key] = duration.amount;
    lifetimeUnits[key] = duration.unit;
});

const isLifetimeRow = (key) => String(key).startsWith('session.lifetime_');

const syncLifetime = (key) => {
    form.settings[key] = durationToMinutes(lifetimeAmounts[key], lifetimeUnits[key]);
};

const formatNumber = (value) => new Intl.NumberFormat('en-NG').format(Number(value) || 0);

const lifetimeHelp = (key) => {
    const minutes = durationToMinutes(lifetimeAmounts[key], lifetimeUnits[key]);
    const days = minutes / 1440;
    const hours = minutes / 60;

    if (minutes <= 0) {
        return 'Enter a duration. Minimum 30 minutes, maximum 365 days.';
    }

    if (days >= 1 && minutes % 1440 === 0) {
        return `That’s ${formatNumber(days)} ${days === 1 ? 'day' : 'days'} of idle time (${formatNumber(minutes)} minutes).`;
    }

    if (hours >= 1 && minutes % 60 === 0) {
        return `That’s ${formatNumber(hours)} ${hours === 1 ? 'hour' : 'hours'} of idle time (${formatNumber(minutes)} minutes).`;
    }

    return `That’s ${formatNumber(minutes)} minutes of idle time.`;
};

const isSecret = (key) => String(key).includes('secret');

const fieldError = (key) => {
    const errors = page.props.errors || {};

    return errors[key] || errors[`settings.${key}`] || null;
};

const save = () => {
    const payload = {};

    editableVisibleRows.value.forEach((row) => {
        let value = form.settings[row.key];

        if (isLifetimeRow(row.key)) {
            syncLifetime(row.key);
            value = Number(form.settings[row.key]);
        }

        if (row.type === 'json') {
            const text = String(value || '').trim();
            if (text.startsWith('[') || text.startsWith('{')) {
                try {
                    value = JSON.parse(text);
                } catch {
                    value = text
                        .split('\n')
                        .map((l) => l.trim())
                        .filter(Boolean);
                }
            } else {
                value = text
                    .split('\n')
                    .map((l) => l.trim())
                    .filter(Boolean);
            }
        }

        if (row.type === 'boolean') {
            value = value === '1' || value === true || value === 1;
        }

        if (row.type === 'integer') {
            value = Number(value);
        }

        payload[row.key] = value;
    });

    processing.value = true;
    router.put(
        route('admin.settings.update'),
        { settings: payload },
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};
</script>
