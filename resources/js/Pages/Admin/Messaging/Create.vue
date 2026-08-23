<template>
    <Head title="Compose message" />

    <AdminChrome title="Compose" :eyebrow="form.audience === 'staff' ? 'Staff' : 'Customers'" />
        <form class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]" @submit.prevent="submit('send')">
            <div class="space-y-4">
                <section class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="block text-[12px] font-semibold text-ink/50">
                            Audience
                            <select
                                v-model="form.audience"
                                class="mt-1.5 w-full rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium text-ink outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                                @change="onAudienceChange"
                            >
                                <option value="users">Customers (artisans)</option>
                                <option value="staff">Staff (ops admins)</option>
                            </select>
                        </label>
                        <label class="block text-[12px] font-semibold text-ink/50">
                            Start from a template
                            <select
                                v-model="selectedTemplateId"
                                class="mt-1.5 w-full rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium text-ink outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                                @change="applyTemplate"
                            >
                                <option value="">Blank message</option>
                                <option v-for="item in audienceTemplates" :key="item.id" :value="String(item.id)">
                                    {{ item.name }}
                                </option>
                            </select>
                        </label>
                    </div>

                    <label class="mt-4 block text-[12px] font-semibold text-ink/50">
                        Internal title
                        <input
                            v-model="form.title"
                            type="text"
                            maxlength="160"
                            placeholder="e.g. July renewal reminder"
                            class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                        />
                    </label>
                    <p v-if="form.errors.title" class="mt-1 text-xs font-semibold text-red-500">{{ form.errors.title }}</p>

                    <label class="mt-4 block text-[12px] font-semibold text-ink/50">
                        Subject
                        <input
                            v-model="form.subject"
                            type="text"
                            maxlength="160"
                            placeholder="What they see in email / the inbox"
                            class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                        />
                    </label>
                    <p v-if="form.errors.subject" class="mt-1 text-xs font-semibold text-red-500">{{ form.errors.subject }}</p>

                    <label class="mt-4 block text-[12px] font-semibold text-ink/50">
                        Body
                        <textarea
                            v-model="form.body"
                            rows="10"
                            maxlength="8000"
                            placeholder="Use {{first_name}} to personalise."
                            class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium leading-relaxed outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                        />
                    </label>
                    <p class="mt-1 text-[12px] font-medium text-ink/40">
                        Placeholders: {{ '{{first_name}}' }}, {{ '{{name}}' }}, {{ '{{business_name}}' }}
                    </p>
                    <p v-if="form.errors.body" class="mt-1 text-xs font-semibold text-red-500">{{ form.errors.body }}</p>
                </section>

                <section
                    v-if="form.audience === 'users'"
                    class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5"
                >
                    <h2 class="text-[15px] font-bold text-ink">Segment</h2>
                    <p class="mt-0.5 text-[13px] font-medium text-ink/45">
                        Leave filters empty to reach everyone. Combine them for a lifecycle send.
                    </p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="block text-[12px] font-semibold text-ink/50">
                            Plan
                            <select
                                v-model="form.segment.plan"
                                class="mt-1.5 w-full rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium text-ink"
                            >
                                <option value="">Any plan</option>
                                <option value="free">Free</option>
                                <option value="payg">Pay-as-you-go</option>
                                <option value="annual">Annual</option>
                            </select>
                        </label>
                        <label class="block text-[12px] font-semibold text-ink/50">
                            Annual plan expiring within
                            <select
                                v-model="form.segment.expiring_days"
                                class="mt-1.5 w-full rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium text-ink"
                            >
                                <option value="">Not used</option>
                                <option value="7">7 days</option>
                                <option value="14">14 days</option>
                                <option value="30">30 days</option>
                            </select>
                        </label>
                        <label class="block text-[12px] font-semibold text-ink/50">
                            Trade
                            <select
                                v-model="form.segment.trade"
                                class="mt-1.5 w-full rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium text-ink"
                            >
                                <option value="">Any trade</option>
                                <option v-for="trade in trades" :key="trade" :value="trade">{{ trade }}</option>
                            </select>
                        </label>
                        <label class="block text-[12px] font-semibold text-ink/50">
                            State
                            <select
                                v-model="form.segment.state"
                                class="mt-1.5 w-full rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium text-ink"
                                @change="form.segment.lga = ''"
                            >
                                <option value="">Any state</option>
                                <option v-for="state in states" :key="state" :value="state">{{ state }}</option>
                            </select>
                        </label>
                        <label class="block text-[12px] font-semibold text-ink/50 sm:col-span-2">
                            City / LGA
                            <select
                                v-model="form.segment.lga"
                                class="mt-1.5 w-full rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium text-ink"
                                :disabled="!form.segment.state"
                            >
                                <option value="">Any city / LGA</option>
                                <option v-for="lga in lgas" :key="lga" :value="lga">{{ lga }}</option>
                            </select>
                        </label>
                    </div>
                </section>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                <section class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5">
                    <h2 class="text-[15px] font-bold text-ink">Channels</h2>
                    <p class="mt-0.5 text-[13px] font-medium text-ink/45">
                        {{
                            form.audience === 'staff'
                                ? 'Staff get in-app and email.'
                                : 'Artisans can also get WhatsApp once a provider is connected.'
                        }}
                    </p>
                    <div class="mt-3 space-y-2">
                        <label
                            v-for="channel in availableChannels"
                            :key="channel.value"
                            class="flex items-center gap-2.5 rounded-xl bg-pale px-3 py-2.5 text-[13px] font-semibold text-ink/70"
                        >
                            <input
                                v-model="form.channels"
                                type="checkbox"
                                :value="channel.value"
                                class="rounded border-ink/20 text-base-action focus:ring-base/20"
                            />
                            {{ channel.label }}
                        </label>
                    </div>
                    <p v-if="form.errors.channels" class="mt-2 text-xs font-semibold text-red-500">
                        {{ form.errors.channels }}
                    </p>
                </section>

                <section class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5">
                    <h2 class="text-[15px] font-bold text-ink">Who gets this</h2>
                    <p class="mt-2 text-2xl font-semibold tabular-nums text-ink">
                        {{ previewCount === null ? '—' : Number(previewCount).toLocaleString() }}
                    </p>
                    <p class="mt-1 text-[13px] font-medium text-ink/45">people in this segment</p>
                    <button
                        type="button"
                        class="mt-3 w-full rounded-xl bg-pale px-4 py-2.5 text-sm font-semibold text-ink disabled:opacity-50"
                        :disabled="previewing"
                        @click="preview"
                    >
                        {{ previewing ? 'Counting…' : 'Count recipients' }}
                    </button>
                </section>

                <section class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5">
                    <h2 class="text-[15px] font-bold text-ink">When</h2>
                    <label class="mt-3 block text-[12px] font-semibold text-ink/50">
                        Schedule for
                        <input
                            v-model="form.send_at"
                            type="datetime-local"
                            class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                        />
                    </label>
                    <p class="mt-1 text-[12px] font-medium text-ink/40">Times are Africa/Lagos.</p>
                    <p v-if="form.errors.send_at" class="mt-1 text-xs font-semibold text-red-500">{{ form.errors.send_at }}</p>

                    <div class="mt-4 grid gap-2">
                        <button
                            type="submit"
                            class="rounded-xl bg-ink px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            Send now
                        </button>
                        <button
                            type="button"
                            class="rounded-xl bg-pale px-4 py-2.5 text-sm font-semibold text-ink disabled:opacity-50"
                            :disabled="form.processing || !form.send_at"
                            @click="submit('schedule')"
                        >
                            Schedule
                        </button>
                        <button
                            type="button"
                            class="rounded-xl px-4 py-2.5 text-sm font-semibold text-ink/50 hover:bg-pale disabled:opacity-50"
                            :disabled="form.processing"
                            @click="submit('draft')"
                        >
                            Save draft
                        </button>
                    </div>
                </section>
            </aside>
        </form>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';

const props = defineProps({
    audience: { type: String, default: 'users' },
    templates: { type: Array, default: () => [] },
    trades: { type: Array, default: () => [] },
    locations: { type: Object, default: () => ({}) },
});

const selectedTemplateId = ref('');
const previewCount = ref(null);
const previewing = ref(false);

const form = useForm({
    audience: props.audience === 'staff' ? 'staff' : 'users',
    announcement_template_id: null,
    title: '',
    subject: '',
    body: '',
    channels: ['in_app', 'email'],
    segment: {
        plan: '',
        expiring_days: '',
        trade: '',
        state: '',
        lga: '',
    },
    action: 'send',
    send_at: '',
});

const audienceTemplates = computed(() =>
    props.templates.filter((item) => item.audience === form.audience),
);

const states = computed(() => Object.keys(props.locations || {}));
const lgas = computed(() => (form.segment.state ? props.locations[form.segment.state] || [] : []));

const availableChannels = computed(() => {
    const channels = [
        { value: 'in_app', label: 'In-app' },
        { value: 'email', label: 'Email' },
    ];
    if (form.audience === 'users') {
        channels.push({ value: 'whatsapp', label: 'WhatsApp' });
    }
    return channels;
});

const onAudienceChange = () => {
    selectedTemplateId.value = '';
    form.announcement_template_id = null;
    if (form.audience === 'staff') {
        form.channels = form.channels.filter((channel) => channel !== 'whatsapp');
        form.segment = { plan: '', expiring_days: '', trade: '', state: '', lga: '' };
    }
    previewCount.value = null;
};

const applyTemplate = () => {
    const template = props.templates.find((item) => String(item.id) === String(selectedTemplateId.value));
    if (!template) {
        form.announcement_template_id = null;
        return;
    }

    form.announcement_template_id = template.id;
    form.title = template.name;
    form.subject = template.subject;
    form.body = template.body;
    form.channels = [...(template.channels || ['in_app', 'email'])];
};

const preview = async () => {
    previewing.value = true;
    try {
        const { data } = await axios.post(route('admin.messaging.preview'), {
            audience: form.audience,
            segment: form.audience === 'staff' ? {} : {
                plan: form.segment.plan || undefined,
                expiring_days: form.segment.expiring_days || undefined,
                trade: form.segment.trade || undefined,
                state: form.segment.state || undefined,
                lga: form.segment.lga || undefined,
            },
        });
        previewCount.value = data.count;
    } catch {
        previewCount.value = null;
    } finally {
        previewing.value = false;
    }
};

const submit = (action) => {
    form.action = action;
    form.post(route('admin.messaging.store'), { preserveScroll: true });
};
</script>
