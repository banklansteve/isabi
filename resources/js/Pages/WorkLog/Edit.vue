<template>
    <Head title="Edit job" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-4xl">
            <AppPageHeader
                title="Edit job"
                :description="editHeadline"
                :back-href="route('work-log.show', entry.uid)"
                back-label="Job details"
            >
                <template #meta>
                    <span
                        v-if="editFlags.review_requested"
                        class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-800 ring-1 ring-amber-200/80"
                    >
                        <i class="ti ti-lock text-sm" aria-hidden="true" />
                        Description & date locked — review requested
                    </span>
                    <span
                        v-else-if="!editFlags.can_edit_description || !editFlags.can_edit_date"
                        class="inline-flex items-center gap-1.5 rounded-full bg-pale px-2.5 py-1 text-[11px] font-semibold text-ink/55 ring-1 ring-ink/[0.06]"
                    >
                        <i class="ti ti-clock text-sm" aria-hidden="true" />
                        Some fields are past the edit window
                    </span>
                </template>
            </AppPageHeader>

            <form class="space-y-6" @submit.prevent="submit">
                <section class="rounded-[1.5rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-7">
                    <div class="mb-5 flex items-center gap-2">
                        <span class="rounded-full bg-tint px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-deep">
                            Core record
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
                            :disabled="!editFlags.can_edit_description"
                            :hint="descriptionHint"
                            :error="form.errors.description"
                        />

                        <FormDatePicker
                            id="worked_on"
                            v-model="form.worked_on"
                            label="Date"
                            :min-date="minDate"
                            :max-date="today"
                            :disabled="!editFlags.can_edit_date"
                            :hint="dateHint"
                            :error="form.errors.worked_on"
                        />
                    </div>
                </section>

                <section class="rounded-[1.5rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-7">
                    <div class="mb-5 flex items-center gap-2">
                        <span class="rounded-full bg-pale px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-ink/50">
                            Details
                        </span>
                        <p class="text-xs font-medium text-ink/40">Photos & notes stay editable</p>
                    </div>

                    <div class="space-y-5">
                        <FormTextInput
                            id="client_name"
                            v-model="form.client_name"
                            label="Client name"
                            icon="ti ti-user"
                            placeholder="e.g. Mrs. Adeyemi"
                            autocomplete="off"
                            hint="Private — only you see this."
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

                        <div class="grid gap-5 sm:grid-cols-2">
                            <FormSelect
                                id="service_state"
                                v-model="form.service_state"
                                label="Service state"
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
                                label="Service LGA"
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
                            :error="form.errors.service_city"
                        />

                        <div v-if="existingMedia.length" class="space-y-2">
                            <p class="text-sm font-semibold text-ink">Current media</p>
                            <ul class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                                <li
                                    v-for="item in existingMedia"
                                    :key="item.id"
                                    class="relative aspect-square overflow-hidden rounded-xl bg-pale ring-1 ring-ink/[0.06]"
                                >
                                    <img
                                        v-if="item.kind === 'image'"
                                        :src="item.url"
                                        :alt="item.original_name"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full flex-col items-center justify-center gap-1 text-ink/40"
                                    >
                                        <i class="ti ti-video text-xl" aria-hidden="true" />
                                        <span class="truncate px-1 text-[10px] font-medium">Video</span>
                                    </div>
                                    <button
                                        type="button"
                                        class="absolute right-1.5 top-1.5 flex h-7 w-7 items-center justify-center rounded-full bg-ink/80 text-white transition-colors hover:bg-ink"
                                        aria-label="Remove media"
                                        @click="removeExisting(item.id)"
                                    >
                                        <i class="ti ti-x text-sm" aria-hidden="true" />
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <FormFileUpload
                            id="media"
                            v-model="form.media"
                            label="Add photos or video"
                            hint="You can add more files or remove existing ones above."
                            button-label="Add more media"
                            help-text="Up to 8 files total · under 5MB each"
                            :error="mediaError"
                        />

                        <FormTextInput
                            id="client_whatsapp"
                            v-model="form.client_whatsapp"
                            type="tel"
                            label="Client’s WhatsApp"
                            icon="ti ti-brand-whatsapp"
                            placeholder="0803 000 0000"
                            inputmode="tel"
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
                            hint="Private — only you see this."
                            :error="form.errors.amount_charged"
                            input-class="tabular-nums"
                        />
                    </div>
                </section>

                <div class="flex justify-center">
                    <FormButton
                        type="submit"
                        variant="primary"
                        class="!min-h-14 !w-full !max-w-none !rounded-2xl !text-base sm:!w-1/2"
                        icon-right="ti ti-check"
                        :loading="form.processing"
                        loading-label="Saving…"
                        label="Save changes"
                    />
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
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    entry: { type: Object, required: true },
    editFlags: { type: Object, required: true },
    maxLookbackDays: { type: Number, default: 14 },
    today: { type: String, required: true },
    minDate: { type: String, required: true },
    jobCategories: {
        type: Object,
        default: () => ({ parents: [], groups: {} }),
    },
    locations: { type: Object, default: () => ({}) },
});

const existingMedia = ref([...(props.entry.media || [])]);

const form = useForm({
    description: props.entry.description || '',
    worked_on: props.entry.worked_on || props.today,
    client_name: props.entry.client_name || '',
    job_category: props.entry.job_category || '',
    job_subcategory: props.entry.job_subcategory || '',
    service_state: props.entry.service_state || '',
    service_lga: props.entry.service_lga || '',
    service_city: props.entry.service_city || '',
    client_whatsapp: props.entry.client_whatsapp || '',
    amount_charged:
        props.entry.amount_charged === null || props.entry.amount_charged === undefined
            ? ''
            : String(props.entry.amount_charged),
    media: [],
    remove_media: [],
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

const editHeadline = computed(() => {
    if (props.editFlags.review_requested) {
        return 'A review was requested — description and date stay locked so the record stays trustworthy.';
    }
    return 'Fix typos, add photos, or update private notes. Core fields lock after a short window.';
});

const descriptionHint = computed(() => {
    if (props.editFlags.review_requested) {
        return 'Locked after a review was requested for this job.';
    }
    if (!props.editFlags.can_edit_description) {
        return `Locked after ${props.editFlags.description_edit_days} days — keeps your work log a real record.`;
    }
    return `Editable for ${props.editFlags.description_edit_days} days after logging, or until a review is requested.`;
});

const dateHint = computed(() => {
    if (props.editFlags.review_requested) {
        return 'Locked after a review was requested for this job.';
    }
    if (!props.editFlags.can_edit_date) {
        return `Locked after ${props.editFlags.date_edit_hours} hours — wrong-day typos get a short window only.`;
    }
    return `You can correct the date within ${props.editFlags.date_edit_hours} hours of logging.`;
});

const onStateChange = () => {
    form.service_lga = '';
};

const removeExisting = (id) => {
    existingMedia.value = existingMedia.value.filter((m) => m.id !== id);
    if (!form.remove_media.includes(id)) {
        form.remove_media.push(id);
    }
};

const normalizeWhatsapp = () => {
    if (form.client_whatsapp) {
        form.client_whatsapp = form.client_whatsapp.replace(/\s+/g, '');
    }
};

const submit = () => {
    normalizeWhatsapp();

    const payload = {
        client_name: form.client_name || null,
        job_category: form.job_category || null,
        job_subcategory: form.job_subcategory || null,
        service_state: form.service_state || null,
        service_lga: form.service_lga || null,
        service_city: form.service_city || null,
        client_whatsapp: form.client_whatsapp || null,
        amount_charged: form.amount_charged === '' ? null : form.amount_charged,
        media: form.media,
        remove_media: form.remove_media,
    };

    if (props.editFlags.can_edit_description) {
        payload.description = form.description;
    }
    if (props.editFlags.can_edit_date) {
        payload.worked_on = form.worked_on;
    }

    form
        .transform(() => payload)
        .post(route('work-log.update', props.entry.uid), {
            forceFormData: true,
            preserveScroll: true,
        });
};
</script>
