<template>
    <Head :title="`Apply · ${vacancy.title}`" />

    <div class="min-h-dvh bg-pale text-ink">
        <PublicTopBar :can-login="canLogin" :can-register="canRegister" />

        <main class="pb-20 sm:pb-24">
            <div class="mx-auto max-w-2xl px-4 pt-6 sm:px-8 sm:pt-10">
                <Link
                    :href="route('careers.show', vacancy.public_uid)"
                    class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-ink/45 hover:text-ink"
                >
                    <i class="ti ti-arrow-left text-sm" aria-hidden="true" />
                    {{ vacancy.title }}
                </Link>

                <header class="mt-5">
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-ink/40">
                        Application
                    </p>
                    <h1 class="mt-2 font-editorial text-[1.75rem] font-semibold tracking-tight text-ink sm:text-[2.1rem]">
                        Apply for {{ vacancy.title }}
                    </h1>
                    <p class="mt-2 text-sm font-medium text-ink/50">
                        Step {{ step + 1 }} of {{ steps.length }} · {{ steps[step].label }}
                        <span v-if="savedHint" class="text-ink/35"> · {{ savedHint }}</span>
                    </p>
                </header>

                <div class="mt-5 h-1.5 overflow-hidden rounded-full bg-ink/[0.06]">
                    <div
                        class="h-full rounded-full bg-base-action transition-all duration-300"
                        :style="{ width: `${((step + 1) / steps.length) * 100}%` }"
                    />
                </div>

                <form class="mt-8 space-y-5" @submit.prevent="onSubmit">
                    <!-- 1 Personal -->
                    <section v-show="step === 0" class="space-y-4">
                        <div class="rounded-2xl bg-white px-4 py-3 ring-1 ring-ink/[0.06]">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-ink/35">Role</p>
                            <p class="mt-1 text-sm font-semibold text-ink">{{ vacancy.title }}</p>
                            <p class="mt-0.5 text-[11px] font-medium text-ink/40">
                                Auto-filled from the vacancy you opened
                            </p>
                        </div>
                        <FormTextInput
                            v-model="form.full_name"
                            label="Full name"
                            required
                            :error="form.errors.full_name"
                        />
                        <FormTextInput
                            v-model="form.email"
                            type="email"
                            label="Email"
                            required
                            :error="form.errors.email"
                        />
                        <FormTextInput
                            v-model="form.phone"
                            label="Phone / WhatsApp"
                            required
                            :error="form.errors.phone"
                        />
                        <FormTextInput
                            v-model="form.city"
                            label="Current city / location"
                            required
                            :error="form.errors.city"
                        />
                        <FormTextInput
                            v-model="form.linkedin_url"
                            label="LinkedIn or portfolio URL (optional)"
                            :error="form.errors.linkedin_url"
                        />
                    </section>

                    <!-- 2 Academic -->
                    <section v-show="step === 1" class="space-y-4">
                        <div
                            v-for="(edu, index) in form.education"
                            :key="index"
                            class="space-y-3 rounded-2xl bg-white p-4 ring-1 ring-ink/[0.06]"
                        >
                            <div class="flex items-center justify-between">
                                <p class="text-[12px] font-bold uppercase tracking-wide text-ink/40">
                                    Qualification {{ index + 1 }}
                                </p>
                                <button
                                    v-if="form.education.length > 1"
                                    type="button"
                                    class="text-[12px] font-bold text-red-600"
                                    @click="removeEducation(index)"
                                >
                                    Remove
                                </button>
                            </div>
                            <FormTextInput
                                v-model="edu.qualification"
                                label="Highest qualification"
                                placeholder="e.g. B.Sc Computer Science"
                                required
                            />
                            <FormTextInput v-model="edu.institution" label="Institution" required />
                            <FormTextInput v-model="edu.field_of_study" label="Field of study" />
                            <div class="grid gap-3 sm:grid-cols-2">
                                <FormTextInput
                                    v-model.number="edu.graduation_year"
                                    type="number"
                                    label="Graduation year"
                                />
                                <FormSelect
                                    v-model="edu.class_of_degree"
                                    label="Class of degree"
                                    placeholder="Select"
                                    :options="classOptions"
                                />
                            </div>
                        </div>
                        <button
                            type="button"
                            class="text-sm font-bold text-base-action"
                            @click="addEducation"
                        >
                            + Add another qualification
                        </button>
                        <FormTextarea
                            v-model="form.certifications"
                            label="Relevant certifications"
                            :rows="2"
                        />
                        <FormTextInput
                            v-model="form.secondary_education"
                            label="Secondary education"
                            placeholder="School · year · result (optional)"
                        />
                        <FormTextInput
                            v-model="form.primary_education"
                            label="Primary education"
                            placeholder="School · year (optional)"
                        />
                        <p v-if="form.errors.education" class="text-sm font-medium text-red-600">
                            {{ form.errors.education }}
                        </p>
                    </section>

                    <!-- 3 Experience -->
                    <section v-show="step === 2" class="space-y-4">
                        <FormCheckbox v-model="form.no_work_experience" label="No prior work experience" />
                        <template v-if="!form.no_work_experience">
                            <div
                                v-for="(job, index) in form.work_experience"
                                :key="index"
                                class="space-y-3 rounded-2xl bg-white p-4 ring-1 ring-ink/[0.06]"
                            >
                                <div class="flex items-center justify-between">
                                    <p class="text-[12px] font-bold uppercase tracking-wide text-ink/40">
                                        Role {{ index + 1 }}
                                    </p>
                                    <button
                                        v-if="form.work_experience.length > 1"
                                        type="button"
                                        class="text-[12px] font-bold text-red-600"
                                        @click="removeExperience(index)"
                                    >
                                        Remove
                                    </button>
                                </div>
                                <FormTextInput v-model="job.company" label="Company / organisation" />
                                <FormTextInput v-model="job.role" label="Role / title" />
                                <FormTextInput
                                    v-model="job.duration"
                                    label="Duration"
                                    placeholder="e.g. Jan 2022 – Present"
                                />
                                <FormTextarea
                                    v-model="job.description"
                                    label="Responsibilities & achievements"
                                    :rows="3"
                                />
                            </div>
                            <button
                                type="button"
                                class="text-sm font-bold text-base-action"
                                @click="addExperience"
                            >
                                + Add another role
                            </button>
                        </template>
                        <p v-else class="text-sm font-medium text-ink/45">
                            That’s fine for entry-level roles — you can skip work history.
                        </p>
                        <p v-if="form.errors.work_experience" class="text-sm font-medium text-red-600">
                            {{ form.errors.work_experience }}
                        </p>
                    </section>

                    <!-- 4 Skills -->
                    <section v-show="step === 3" class="space-y-4">
                        <p class="text-sm font-medium text-ink/50">Select skills that apply:</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="skill in skillOptions"
                                :key="skill"
                                type="button"
                                class="rounded-full px-3 py-1.5 text-[12px] font-bold transition-colors"
                                :class="
                                    form.skills.includes(skill)
                                        ? 'bg-base-action text-white'
                                        : 'bg-white text-ink/55 ring-1 ring-ink/10 hover:bg-pale'
                                "
                                @click="toggleSkill(skill)"
                            >
                                {{ skill }}
                            </button>
                        </div>
                        <FormTextarea
                            v-model="form.skills_other"
                            label="Other skills (optional)"
                            hint="Anything not covered above"
                            :rows="2"
                        />
                    </section>

                    <!-- 5 Achievements -->
                    <section v-show="step === 4" class="space-y-4">
                        <FormTextarea
                            v-model="form.achievements"
                            label="Achievements (optional)"
                            hint="Awards, notable projects, publications"
                            :rows="5"
                        />
                    </section>

                    <!-- 6 Nigerian context -->
                    <section v-show="step === 5" class="space-y-4">
                        <FormSelect
                            v-model="form.nysc_status"
                            label="NYSC status"
                            required
                            placeholder="Select"
                            :options="nyscOptions"
                            :error="form.errors.nysc_status"
                        />
                        <FormCheckbox
                            v-model="form.willing_to_relocate"
                            label="Willing to relocate if required"
                        />
                        <FormSelect
                            v-model="form.preferred_work_mode"
                            label="Preferred work mode"
                            placeholder="Select"
                            :options="workModeOptions"
                        />
                        <FormTextInput
                            v-model="form.earliest_availability"
                            label="Earliest availability / notice period"
                            placeholder="e.g. Immediately · 2 weeks · 1 month"
                        />
                    </section>

                    <!-- 7 Role-specific -->
                    <section v-show="step === 6" class="space-y-4">
                        <FormTextInput
                            v-model="form.expected_salary"
                            label="Expected salary (or range)"
                            placeholder="e.g. ₦250,000 – ₦350,000 / month"
                        />
                        <FormTextInput
                            v-model="form.notice_period"
                            label="Notice period from current role"
                            placeholder="e.g. 30 days · Not employed"
                        />
                        <FormTextarea
                            v-model="form.why_this_role"
                            label="Why this role? (optional)"
                            hint="Keep it short — max ~800 characters"
                            :rows="4"
                            :error="form.errors.why_this_role"
                        />
                    </section>

                    <!-- 8 Documents -->
                    <section v-show="step === 7" class="space-y-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-ink/70">
                                CV / resume <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="file"
                                accept=".pdf,.doc,.docx,application/pdf"
                                class="block w-full text-sm font-medium text-ink/60 file:mr-3 file:rounded-xl file:border-0 file:bg-base-action file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-base-hover"
                                @change="onCvPick"
                            />
                            <p v-if="cvName" class="mt-1.5 text-[12px] font-semibold text-ink/45">
                                Selected: {{ cvName }}
                            </p>
                            <p v-if="form.errors.cv" class="mt-1 text-sm font-medium text-red-600">
                                {{ form.errors.cv }}
                            </p>
                            <p class="mt-1 text-[11px] font-medium text-ink/35">
                                PDF or Word, max 5MB. Re-select if you refreshed mid-application.
                            </p>
                        </div>
                        <FormTextInput
                            v-model="form.work_sample_url"
                            label="Portfolio / work-sample link (optional)"
                        />
                        <div
                            v-for="(ref, index) in form.references"
                            :key="index"
                            class="space-y-3 rounded-2xl bg-white p-4 ring-1 ring-ink/[0.06]"
                        >
                            <p class="text-[12px] font-bold uppercase tracking-wide text-ink/40">
                                Reference {{ index + 1 }} (optional)
                            </p>
                            <FormTextInput v-model="ref.name" label="Name" />
                            <FormTextInput v-model="ref.relationship" label="Relationship" />
                            <FormTextInput v-model="ref.contact" label="Contact (email or phone)" />
                        </div>

                        <label class="flex items-start gap-3 rounded-2xl bg-white p-4 ring-1 ring-ink/[0.06]">
                            <input
                                v-model="form.ndpr_consent"
                                type="checkbox"
                                class="mt-1 rounded border-ink/20 text-base-action"
                                required
                            />
                            <span class="text-sm font-medium leading-relaxed text-ink/60">
                                I consent to Kraftrack collecting and processing my personal data
                                (including education, employment history, and salary expectations) for
                                recruitment purposes, in line with the Nigeria Data Protection Act
                                (NDPR). I understand my data will be stored securely and used only for
                                evaluating this application.
                                <span class="text-red-500">*</span>
                            </span>
                        </label>
                        <p v-if="form.errors.ndpr_consent" class="text-sm font-medium text-red-600">
                            {{ form.errors.ndpr_consent }}
                        </p>
                    </section>

                    <p v-if="stepError" class="text-sm font-medium text-red-600">{{ stepError }}</p>

                    <div class="flex flex-wrap gap-2 pt-2">
                        <FormButton
                            v-if="step > 0"
                            type="button"
                            variant="secondary"
                            :disabled="form.processing"
                            @click="prevStep"
                        >
                            Back
                        </FormButton>
                        <FormButton
                            v-if="step < steps.length - 1"
                            type="button"
                            variant="primary"
                            @click="nextStep"
                        >
                            Continue
                        </FormButton>
                        <FormButton
                            v-else
                            type="submit"
                            variant="primary"
                            :loading="form.processing"
                        >
                            Submit application
                        </FormButton>
                    </div>
                </form>
            </div>
        </main>

        <SiteFooter :can-register="canRegister" />
    </div>
</template>

<script setup>
import FormButton from '@/Components/Form/FormButton.vue';
import FormCheckbox from '@/Components/Form/FormCheckbox.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import PublicTopBar from '@/Components/Marketing/PublicTopBar.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    canLogin: { type: Boolean, default: true },
    canRegister: { type: Boolean, default: true },
    vacancy: { type: Object, required: true },
    skillOptions: { type: Array, default: () => [] },
    classOfDegreeOptions: { type: Array, default: () => [] },
    nyscOptions: { type: Array, default: () => [] },
    workModeOptions: { type: Array, default: () => [] },
});

const steps = [
    { key: 'personal', label: 'Personal' },
    { key: 'academic', label: 'Academic' },
    { key: 'experience', label: 'Experience' },
    { key: 'skills', label: 'Skills' },
    { key: 'achievements', label: 'Achievements' },
    { key: 'nigeria', label: 'Nigeria context' },
    { key: 'role', label: 'Role details' },
    { key: 'documents', label: 'Documents' },
];

const blankEducation = () => ({
    qualification: '',
    institution: '',
    field_of_study: '',
    graduation_year: null,
    class_of_degree: '',
});

const blankExperience = () => ({
    company: '',
    role: '',
    duration: '',
    description: '',
});

const blankReference = () => ({
    name: '',
    relationship: '',
    contact: '',
});

const storageKey = `kraftrack-career-apply-${props.vacancy.public_uid}`;

const step = ref(0);
const stepError = ref('');
const savedHint = ref('');
const cvName = ref('');

const form = useForm({
    full_name: '',
    email: '',
    phone: '',
    city: '',
    linkedin_url: '',
    portfolio_url: '',
    education: [blankEducation()],
    certifications: '',
    secondary_education: '',
    primary_education: '',
    no_work_experience: false,
    work_experience: [blankExperience()],
    skills: [],
    skills_other: '',
    achievements: '',
    nysc_status: '',
    willing_to_relocate: false,
    preferred_work_mode: '',
    earliest_availability: '',
    expected_salary: '',
    notice_period: '',
    why_this_role: '',
    cv: null,
    work_sample_url: '',
    references: [blankReference(), blankReference()],
    ndpr_consent: false,
});

const classOptions = computed(() =>
    (props.classOfDegreeOptions || []).map((v) => ({ value: v, label: v })),
);

const persistable = () => {
    const data = { ...form.data(), step: step.value };
    delete data.cv;
    return data;
};

const saveProgress = () => {
    try {
        localStorage.setItem(storageKey, JSON.stringify(persistable()));
        savedHint.value = 'Progress saved';
        window.setTimeout(() => {
            savedHint.value = '';
        }, 1800);
    } catch {
        // ignore quota / private mode
    }
};

const loadProgress = () => {
    try {
        const raw = localStorage.getItem(storageKey);
        if (!raw) return;
        const data = JSON.parse(raw);
        Object.keys(form.data()).forEach((key) => {
            if (key === 'cv') return;
            if (data[key] !== undefined) form[key] = data[key];
        });
        if (typeof data.step === 'number') {
            step.value = Math.min(Math.max(0, data.step), steps.length - 1);
        }
        if (!form.education?.length) form.education = [blankEducation()];
        if (!form.work_experience?.length) form.work_experience = [blankExperience()];
        if (!form.references?.length) form.references = [blankReference(), blankReference()];
        if (!Array.isArray(form.skills)) form.skills = [];
    } catch {
        // ignore corrupt storage
    }
};

onMounted(() => {
    loadProgress();
});

watch([form, step], () => {
    saveProgress();
}, { deep: true });

const addEducation = () => form.education.push(blankEducation());
const removeEducation = (index) => form.education.splice(index, 1);
const addExperience = () => form.work_experience.push(blankExperience());
const removeExperience = (index) => form.work_experience.splice(index, 1);

const toggleSkill = (skill) => {
    const idx = form.skills.indexOf(skill);
    if (idx >= 0) form.skills.splice(idx, 1);
    else form.skills.push(skill);
};

const onCvPick = (event) => {
    const file = event.target.files?.[0] || null;
    form.cv = file;
    cvName.value = file?.name || '';
};

const validateStep = () => {
    stepError.value = '';
    if (step.value === 0) {
        if (!form.full_name?.trim() || !form.email?.trim() || !form.phone?.trim() || !form.city?.trim()) {
            stepError.value = 'Please fill in your name, email, phone, and city.';
            return false;
        }
    }
    if (step.value === 1) {
        const first = form.education[0];
        if (!first?.qualification?.trim() || !first?.institution?.trim()) {
            stepError.value = 'Add at least one qualification with institution.';
            return false;
        }
    }
    if (step.value === 2 && !form.no_work_experience) {
        const has = form.work_experience.some((j) => j.company?.trim() || j.role?.trim());
        if (!has) {
            stepError.value = 'Add work experience or tick “No prior work experience”.';
            return false;
        }
    }
    if (step.value === 5 && !form.nysc_status) {
        stepError.value = 'Select your NYSC status.';
        return false;
    }
    if (step.value === 7) {
        if (!form.cv) {
            stepError.value = 'Upload your CV before submitting.';
            return false;
        }
        if (!form.ndpr_consent) {
            stepError.value = 'NDPR consent is required to submit.';
            return false;
        }
    }
    return true;
};

const nextStep = () => {
    if (!validateStep()) return;
    saveProgress();
    step.value = Math.min(step.value + 1, steps.length - 1);
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const prevStep = () => {
    stepError.value = '';
    step.value = Math.max(step.value - 1, 0);
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const onSubmit = () => {
    if (!validateStep()) return;

    form
        .transform((data) => ({
            ...data,
            portfolio_url: data.linkedin_url || data.portfolio_url || null,
            preferred_work_mode: data.preferred_work_mode || null,
            work_experience: data.no_work_experience ? [] : data.work_experience,
        }))
        .post(route('careers.apply.store', props.vacancy.public_uid), {
            forceFormData: true,
            onSuccess: () => {
                try {
                    localStorage.removeItem(storageKey);
                } catch {
                    // ignore
                }
            },
        });
};
</script>
