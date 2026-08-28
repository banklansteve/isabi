<template>
    <Head title="Settings" />

    <AdminChrome title="Settings" eyebrow="Global configuration" />
        <form class="space-y-4" @submit.prevent="save">
            <section
                v-if="tab === 'session'"
                class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5"
            >
                <h2 class="text-sm font-bold text-ink">Login session duration</h2>
                <p class="mt-1 text-[13px] font-medium text-ink/45">
                    How long each kind of account stays signed in after their last activity. New values apply to the next request — including people already signed in.
                </p>
            </section>

            <p v-if="!visibleRows.length" class="rounded-2xl bg-white p-8 text-center text-sm font-medium text-ink/40 shadow-premium ring-1 ring-ink/[0.05]">
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
                <p v-if="fieldError(row.key)" class="mt-2 text-[13px] font-semibold text-coral-deep">
                    {{ fieldError(row.key) }}
                </p>
            </div>

            <FormButton
                v-if="visibleRows.length"
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
import FormButton from '@/Components/Form/FormButton.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
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
};

const { tab } = useAdminTabs({ tab: 'general' });

const visibleRows = computed(() => {
    const groups = tabGroups[tab.value] || [tab.value];
    return groups.flatMap((group) => props.settings[group] || []);
});

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

    visibleRows.value.forEach((row) => {
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
