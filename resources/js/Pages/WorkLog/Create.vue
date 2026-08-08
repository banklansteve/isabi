<template>
    <Head title="Log a job" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-2xl">
            <AppPageHeader
                title="Log a job"
                :description="headerDescription"
                :back-href="route('work-log.index')"
                back-label="Work log"
            >
                <template #meta>
                    <FormStepProgress
                        v-model="step"
                        :steps="steps"
                        :max-reachable="maxReachable"
                    />
                </template>
            </AppPageHeader>

            <form class="space-y-5" @submit.prevent="onFormSubmit">
                <!-- Step 1: Required -->
                <section
                    v-show="step === 0"
                    class="step-panel rounded-[1.5rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-7"
                >
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-ink">The essentials</p>
                            <p class="mt-0.5 text-xs font-medium text-ink/45">
                                Required — this is enough to save a real job record.
                            </p>
                        </div>
                        <span class="rounded-full bg-tint px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-deep">
                            Required
                        </span>
                    </div>

                    <div class="space-y-5">
                        <FormTextInput
                            id="description"
                            v-model="form.description"
                            label="What was done"
                            icon="ti ti-tool"
                            placeholder="e.g. Fixed kitchen sink leak"
                            autocomplete="off"
                            :error="form.errors.description || localErrors.description"
                            autofocus
                        />

                        <FormDatePicker
                            id="worked_on"
                            v-model="form.worked_on"
                            label="Date"
                            :min-date="minDate"
                            :max-date="today"
                            :hint="`You can log jobs from the last ${maxLookbackDays} days — keeps the record honest.`"
                            :error="form.errors.worked_on || localErrors.worked_on"
                        />
                    </div>
                </section>

                <!-- Step 2: Optional -->
                <section
                    v-show="step === 1"
                    class="step-panel rounded-[1.5rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-7"
                >
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-ink">Nice to have</p>
                            <p class="mt-0.5 text-xs font-medium text-ink/45">
                                Optional — skip anytime. These make discovery, reviews, and recall easier later.
                            </p>
                        </div>
                        <span class="rounded-full bg-pale px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-ink/50">
                            Optional
                        </span>
                    </div>

                    <div class="space-y-5">
                        <FormTextInput
                            id="client_name"
                            v-model="form.client_name"
                            label="Client name"
                            icon="ti ti-user"
                            placeholder="e.g. Mrs. Adeyemi"
                            autocomplete="off"
                            hint="Private — only you see this. Helps you spot entries later (“Mrs. Adeyemi’s kitchen”)."
                            :error="form.errors.client_name"
                        />

                        <FormSelect
                            id="job_category"
                            v-model="form.job_category"
                            label="Job category"
                            icon="ti ti-category"
                            placeholder="Select a category"
                            :options="categoryParents"
                            searchable
                            search-placeholder="Search categories…"
                            hint="Pick the broad type of work — then a more specific subcategory."
                            :error="form.errors.job_category"
                            @change="onCategoryChange"
                        />

                        <FormSelect
                            id="job_subcategory"
                            v-model="form.job_subcategory"
                            label="Subcategory"
                            icon="ti ti-tags"
                            :placeholder="form.job_category ? 'Select a subcategory' : 'Choose a category first'"
                            :options="subcategoryOptions"
                            searchable
                            search-placeholder="Search subcategories…"
                            :disabled="!form.job_category"
                            :error="form.errors.job_subcategory"
                        />

                        <div class="rounded-2xl bg-pale/80 p-4 ring-1 ring-ink/[0.04] sm:p-5">
                            <div class="mb-3">
                                <p class="text-sm font-semibold text-ink">Service location</p>
                                <p class="mt-0.5 text-xs font-medium text-ink/45">
                                    Reinforces your verified service area and future “jobs near you” proof.
                                </p>
                            </div>
                            <div class="space-y-4">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <FormSelect
                                        id="service_state"
                                        v-model="form.service_state"
                                        label="State"
                                        icon="ti ti-map-pin"
                                        placeholder="Select state"
                                        :options="states"
                                        searchable
                                        search-placeholder="Search states…"
                                        :error="form.errors.service_state"
                                        @change="onStateChange"
                                    />
                                    <FormSelect
                                        id="service_lga"
                                        v-model="form.service_lga"
                                        label="LGA"
                                        icon="ti ti-building-community"
                                        :placeholder="form.service_state ? 'Select LGA' : 'Choose a state first'"
                                        :options="lgas"
                                        searchable
                                        search-placeholder="Search LGAs…"
                                        :disabled="!form.service_state"
                                        :error="form.errors.service_lga"
                                    />
                                </div>
                                <FormTextInput
                                    id="service_city"
                                    v-model="form.service_city"
                                    label="City or town"
                                    icon="ti ti-map-2"
                                    placeholder="e.g. Ikeja, Bodija"
                                    autocomplete="address-level2"
                                    :error="form.errors.service_city"
                                />
                            </div>
                        </div>

                        <FormFileUpload
                            id="media"
                            v-model="form.media"
                            label="Photos or video"
                            hint="Before/after shots help clients trust your page — skip if you’re in a rush."
                            button-label="Add photos or video"
                            help-text="Up to 8 files · images & videos under 5MB each"
                            :error="mediaError"
                            @previews="onMediaPreviews"
                        />

                        <FormTextInput
                            id="client_whatsapp"
                            v-model="form.client_whatsapp"
                            type="tel"
                            label="Client’s WhatsApp"
                            icon="ti ti-brand-whatsapp"
                            placeholder="0803 000 0000"
                            inputmode="tel"
                            autocomplete="tel"
                            hint="Needed when you want to request a review for this job."
                            :error="form.errors.client_whatsapp"
                            @blur="normalizeWhatsapp"
                        />

                        <FormTextInput
                            id="amount_charged"
                            v-model="form.amount_charged"
                            type="number"
                            label="Amount charged"
                            icon="ti ti-cash"
                            placeholder="0"
                            inputmode="decimal"
                            hint="Private — only you see this. Never shown on your public page."
                            :error="form.errors.amount_charged"
                            input-class="tabular-nums"
                        />
                    </div>
                </section>

                <!-- Step 3: Preview -->
                <section
                    v-show="step === 2"
                    class="step-panel rounded-[1.5rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-7"
                >
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-ink">Preview & save</p>
                            <p class="mt-0.5 text-xs font-medium text-ink/45">
                                Quick check before this becomes part of your work record.
                            </p>
                        </div>
                        <span class="rounded-full bg-tint px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-deep">
                            Review
                        </span>
                    </div>

                    <div class="overflow-hidden rounded-2xl ring-1 ring-ink/[0.06]">
                        <div class="border-b border-ink/[0.06] bg-gradient-to-br from-pale to-white px-4 py-4 sm:px-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink/40">
                                Job record
                            </p>
                            <p class="mt-1.5 text-lg font-bold tracking-tight text-ink">
                                {{ form.description || '—' }}
                            </p>
                            <p class="mt-1 text-sm font-medium text-ink/50">
                                {{ workedOnLabel }}
                            </p>
                        </div>

                        <dl class="divide-y divide-ink/[0.06]">
                            <div
                                v-for="row in previewRows"
                                :key="row.label"
                                class="flex items-start justify-between gap-4 px-4 py-3.5 sm:px-5"
                            >
                                <dt class="shrink-0 text-xs font-semibold text-ink/40">
                                    {{ row.label }}
                                </dt>
                                <dd class="text-right text-sm font-semibold text-ink">
                                    {{ row.value }}
                                    <span
                                        v-if="row.private"
                                        class="mt-0.5 block text-[10px] font-medium text-ink/35"
                                    >
                                        Private
                                    </span>
                                </dd>
                            </div>
                        </dl>

                        <div class="border-t border-ink/[0.06] px-4 py-4 sm:px-5">
                            <div class="mb-2.5 flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink/40">
                                    Media
                                </p>
                                <p class="text-xs font-medium text-ink/40">
                                    {{ mediaPreviews.length ? `${mediaPreviews.length} file${mediaPreviews.length === 1 ? '' : 's'}` : 'None added' }}
                                </p>
                            </div>

                            <ul
                                v-if="mediaPreviews.length"
                                class="grid grid-cols-3 gap-2 sm:grid-cols-4"
                            >
                                <li
                                    v-for="item in mediaPreviews"
                                    :key="item.id"
                                    class="aspect-square overflow-hidden rounded-xl bg-pale ring-1 ring-ink/10"
                                >
                                    <img
                                        v-if="item.kind === 'image'"
                                        :src="item.url"
                                        :alt="item.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full flex-col items-center justify-center gap-1 px-2 text-center"
                                    >
                                        <i class="ti ti-video text-xl text-ink/40" aria-hidden="true" />
                                        <span class="line-clamp-2 text-[10px] font-medium text-ink/50">
                                            {{ item.name }}
                                        </span>
                                    </div>
                                </li>
                            </ul>
                            <p
                                v-else
                                class="rounded-xl bg-pale px-3 py-4 text-center text-xs font-medium text-ink/40"
                            >
                                No photos or video — that’s fine. You can add them later when editing.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Actions -->
                <div class="flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <FormButton
                        v-if="step > 0"
                        type="button"
                        variant="secondary"
                        class="!rounded-2xl sm:!min-w-[7.5rem]"
                        icon-left="ti ti-arrow-left"
                        label="Back"
                        @click="goBack"
                    />
                    <span v-else class="hidden sm:block sm:min-w-[7.5rem]" />

                    <div class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                        <button
                            v-if="step === 1"
                            type="button"
                            class="tap-target text-center text-sm font-semibold text-ink/45 transition-colors hover:text-ink sm:px-2"
                            @click="skipToPreview"
                        >
                            Skip to preview
                        </button>

                        <FormButton
                            v-if="step < 2"
                            type="button"
                            variant="primary"
                            class="!min-h-12 !rounded-2xl !px-8 sm:!min-w-[11rem]"
                            :icon-right="step === 0 ? 'ti ti-arrow-right' : 'ti ti-eye'"
                            :label="step === 0 ? 'Continue' : 'Preview'"
                            @click="goNext"
                        />

                        <FormButton
                            v-else
                            type="submit"
                            variant="primary"
                            class="!min-h-12 !rounded-2xl !px-8 sm:!min-w-[11rem]"
                            icon-right="ti ti-check"
                            :loading="form.processing"
                            loading-label="Saving job…"
                            label="Save job"
                        />
                    </div>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AppPageHeader from '@/Components/AppPageHeader.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormDatePicker from '@/Components/Form/FormDatePicker.vue';
import FormFileUpload from '@/Components/Form/FormFileUpload.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import FormStepProgress from '@/Components/Form/FormStepProgress.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    maxLookbackDays: { type: Number, default: 14 },
    today: { type: String, required: true },
    minDate: { type: String, required: true },
    jobCategories: { type: Array, default: () => [] },
    locations: { type: Object, default: () => ({}) },
    defaults: {
        type: Object,
        default: () => ({ service_state: null, service_lga: null }),
    },
});

const step = ref(0);
const maxReachable = ref(0);
const mediaPreviews = ref([]);
const localErrors = reactive({
    description: '',
    worked_on: '',
});

const steps = [
    { key: 'essentials', label: 'Job', hint: 'Required' },
    { key: 'details', label: 'Details', hint: 'Optional' },
    { key: 'preview', label: 'Preview', hint: 'Save' },
];

const form = useForm({
    description: '',
    worked_on: props.today,
    client_name: '',
    job_category: '',
    job_subcategory: '',
    service_state: props.defaults.service_state || '',
    service_lga: props.defaults.service_lga || '',
    service_city: '',
    client_whatsapp: '',
    amount_charged: '',
    media: [],
});

const states = computed(() => Object.keys(props.locations || {}));

const categoryParents = computed(() => props.jobCategories?.parents || []);

const subcategoryOptions = computed(() => {
    if (!form.job_category) {
        return [];
    }
    return props.jobCategories?.groups?.[form.job_category] || [];
});

const lgas = computed(() => {
    if (!form.service_state) {
        return [];
    }
    return props.locations[form.service_state] || [];
});

const onCategoryChange = () => {
    form.job_subcategory = '';
};

const mediaError = computed(() => {
    if (form.errors.media) {
        return form.errors.media;
    }
    const keyed = Object.keys(form.errors).find((k) => k.startsWith('media.'));
    return keyed ? form.errors[keyed] : '';
});

const onMediaPreviews = (items) => {
    mediaPreviews.value = items || [];
};

const headerDescription = computed(() => {
    if (step.value === 0) {
        return 'Start with what you did and when. Everything else is optional.';
    }
    if (step.value === 1) {
        return 'Add context that helps later — photos, client notes, location, and more.';
    }
    return 'Confirm the details, then save this job to your work record.';
});

const workedOnLabel = computed(() => {
    if (!form.worked_on) {
        return '—';
    }
    try {
        return new Date(`${form.worked_on}T12:00:00`).toLocaleDateString(undefined, {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    } catch {
        return form.worked_on;
    }
});

const serviceLabel = computed(() => {
    const parts = [form.service_city, form.service_lga, form.service_state].filter(Boolean);
    return parts.length ? parts.join(', ') : '—';
});

const amountLabel = computed(() => {
    if (form.amount_charged === '' || form.amount_charged === null) {
        return '—';
    }
    const n = Number(form.amount_charged);
    if (Number.isNaN(n)) {
        return '—';
    }
    return `₦${n.toLocaleString(undefined, { maximumFractionDigits: 2 })}`;
});

const categoryPreview = computed(() => {
    if (form.job_subcategory && form.job_category) {
        return `${form.job_subcategory} · ${form.job_category}`;
    }
    return form.job_subcategory || form.job_category || '—';
});

const previewRows = computed(() => [
    { label: 'Client', value: form.client_name || '—', private: !!form.client_name },
    { label: 'Category', value: categoryPreview.value },
    { label: 'Location', value: serviceLabel.value },
    { label: 'WhatsApp', value: form.client_whatsapp || '—' },
    { label: 'Amount', value: amountLabel.value, private: form.amount_charged !== '' },
]);

const onStateChange = () => {
    form.service_lga = '';
};

const normalizeWhatsapp = () => {
    if (form.client_whatsapp) {
        form.client_whatsapp = form.client_whatsapp.replace(/\s+/g, '');
    }
};

const validateStep1 = () => {
    localErrors.description = '';
    localErrors.worked_on = '';

    const desc = form.description.trim();
    if (desc.length < 3) {
        localErrors.description = desc
            ? 'Add a bit more detail so this entry is meaningful.'
            : 'Tell us what was done — even a short line is enough.';
    }
    if (!form.worked_on) {
        localErrors.worked_on = 'Pick the date this job was done.';
    }

    return !localErrors.description && !localErrors.worked_on;
};

const goNext = () => {
    if (step.value === 0 && !validateStep1()) {
        return;
    }
    step.value = Math.min(step.value + 1, 2);
    maxReachable.value = Math.max(maxReachable.value, step.value);
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const goBack = () => {
    step.value = Math.max(step.value - 1, 0);
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const skipToPreview = () => {
    step.value = 2;
    maxReachable.value = Math.max(maxReachable.value, 2);
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const onFormSubmit = () => {
    if (step.value !== 2) {
        goNext();
        return;
    }
    submit();
};

const submit = () => {
    if (!validateStep1()) {
        step.value = 0;
        return;
    }

    normalizeWhatsapp();

    form
        .transform((data) => ({
            ...data,
            client_name: data.client_name || null,
            job_category: data.job_category || null,
            job_subcategory: data.job_subcategory || null,
            service_state: data.service_state || null,
            service_lga: data.service_lga || null,
            service_city: data.service_city || null,
            client_whatsapp: data.client_whatsapp || null,
            amount_charged: data.amount_charged === '' ? null : data.amount_charged,
        }))
        .post(route('work-log.store'), {
            forceFormData: true,
            preserveScroll: true,
            onError: (errors) => {
                if (errors.description || errors.worked_on) {
                    step.value = 0;
                } else if (
                    errors.media ||
                    Object.keys(errors).some((k) => k.startsWith('media.')) ||
                    errors.client_whatsapp ||
                    errors.job_category ||
                    errors.job_subcategory ||
                    errors.service_state ||
                    errors.service_lga
                ) {
                    step.value = 1;
                    maxReachable.value = Math.max(maxReachable.value, 1);
                }
            },
        });
};

watch(
    () => [form.errors.description, form.errors.worked_on, form.errors.media, mediaError.value],
    () => {
        if (form.errors.description || form.errors.worked_on) {
            step.value = 0;
        }
    },
);
</script>

<style scoped>
.step-panel {
    animation: soft-panel 280ms cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes soft-panel {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
