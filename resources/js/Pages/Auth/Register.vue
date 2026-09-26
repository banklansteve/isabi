<template>
    <AuthLayout
        headline="Build the page that proves your work."
        support="A few details now — that's all it takes to get started. We'll help you round out your profile with a photo and bio once you're in."
        :points="[
            'Free to start, no card required',
            'WhatsApp review links from real clients',
            'A public page, ready to share anywhere',
        ]"
    >
        <Head title="Create account" />

        <div class="auth-enter">
            <!-- Same-screen email verification (after account create) -->
            <div v-if="pendingVerification" class="space-y-6">
                <div>
                    <p class="text-center text-xs font-bold uppercase tracking-[0.16em] text-ink/40">
                        Confirm email
                    </p>
                    <h1 class="mt-2.5 text-center font-display text-3xl font-extrabold tracking-tight text-ink sm:text-[2.1rem]">
                        Enter your code
                    </h1>
                    <p class="mt-2.5 text-center text-sm font-semibold leading-relaxed text-ink/55">
                        We sent a 6-digit code to
                        <span class="font-bold text-ink">{{ pendingVerification.email }}</span>.
                        Stay here — no need to open another tab.
                    </p>
                </div>

                <AppInlineAlert
                    v-if="verifyStatus === 'verification-link-sent'"
                    tone="success"
                    title="New code sent"
                    message="Check your inbox (and spam) for a fresh 6-digit code."
                />

                <AppInlineAlert
                    v-if="verifyForm.errors.code || verifyForm.errors.email"
                    tone="error"
                    title="Couldn’t verify"
                    :message="verifyForm.errors.code || verifyForm.errors.email"
                />

                <form class="space-y-4" @submit.prevent="submitVerify">
                    <FormTextInput
                        id="code"
                        :model-value="verifyForm.code"
                        label="Verification code"
                        icon="ti ti-password"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        placeholder="6-digit code"
                        maxlength="6"
                        :error="verifyForm.errors.code"
                        @update:model-value="onCodeInput"
                    />

                    <FormButton
                        type="submit"
                        variant="primary"
                        block
                        label="Verify &amp; continue"
                        :loading="verifyForm.processing"
                        loading-label="Verifying…"
                        icon-right="ti ti-check"
                    />
                </form>

                <div class="rounded-2xl bg-pale/80 px-4 py-3.5 ring-1 ring-ink/[0.06]">
                    <p class="text-sm font-medium text-ink/60">
                        Code expires in about {{ pendingVerification.codeTtlMinutes || 15 }} minutes.
                        Wrong or expired? Request a new one — the previous code stops working immediately.
                    </p>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <FormButton
                            type="button"
                            variant="secondary"
                            :label="resendLabel"
                            :disabled="cooldown > 0 || resendForm.processing"
                            :loading="resendForm.processing"
                            loading-label="Sending…"
                            @click="resendCode"
                        />
                        <Link
                            :href="route('dashboard')"
                            class="text-sm font-bold text-base-action hover:text-base-hover"
                        >
                            Continue to dashboard
                        </Link>
                    </div>
                    <p class="mt-2 text-xs font-medium text-ink/40">
                        You can explore the app now. Sending review requests stays locked until you verify.
                    </p>
                </div>
            </div>

            <template v-else>
            <div class="mb-8">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-base">
                        Step {{ step }} of {{ steps.length }}
                    </p>
                    <p class="text-xs font-semibold text-ink/40">
                        {{ Math.round(progress) }}% complete
                    </p>
                </div>
                <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-ink/8">
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-base to-coral transition-all duration-500 ease-out"
                        :style="{ width: `${progress}%` }"
                    />
                </div>
                <div class="mt-4 flex gap-1.5">
                    <button
                        v-for="(s, i) in steps"
                        :key="s.key"
                        type="button"
                        class="h-1.5 flex-1 rounded-full transition-colors duration-300"
                        :class="i + 1 <= step ? 'bg-base' : 'bg-ink/10'"
                        :aria-label="`Go to ${s.title}`"
                        :disabled="i + 1 > step"
                        @click="goTo(i + 1)"
                    />
                </div>
            </div>

            <Transition name="step" mode="out-in">
                <div :key="step">
                    <p class="text-center text-xs font-bold uppercase tracking-[0.16em] text-ink/40">
                        {{ currentStep.eyebrow }}
                    </p>
                    <h1 class="mt-2.5 text-center font-display text-3xl font-extrabold tracking-tight text-ink sm:text-[2.1rem]">
                        {{ currentStep.title }}
                    </h1>
                    <p class="mt-2.5 text-center text-sm font-semibold leading-relaxed text-ink/55">
                        {{ currentStep.support }}
                    </p>

                    <!-- Step 1: Identity -->
                    <div v-if="step === 1" class="mt-8 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <FormTextInput
                                id="first_name"
                                v-model="form.first_name"
                                label="First name"
                                icon="ti ti-user"
                                placeholder="Chidi"
                                autocomplete="given-name"
                                :error="displayError('first_name')"
                                @blur="validateField('first_name')"
                            />
                            <FormTextInput
                                id="last_name"
                                v-model="form.last_name"
                                label="Last name"
                                icon="ti ti-user"
                                placeholder="Okafor"
                                autocomplete="family-name"
                                :error="displayError('last_name')"
                                @blur="validateField('last_name')"
                            />
                        </div>
                        <FormTextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            label="Email address"
                            icon="ti ti-mail"
                            placeholder="you@example.com"
                            autocomplete="email"
                            :error="displayError('email')"
                            @blur="validateField('email')"
                        />
                        <FormTextInput
                            id="business_name"
                            v-model="form.business_name"
                            label="Business / public name"
                            icon="ti ti-building-store"
                            placeholder="e.g. Chidi Plumbing"
                            hint="This becomes your public page URL."
                            :error="displayError('business_name')"
                            @blur="validateField('business_name')"
                        />
                        <p
                            v-if="slugPreview"
                            class="rounded-xl bg-tint/60 px-3.5 py-2.5 text-xs font-semibold text-deep ring-1 ring-base/10"
                        >
                            Your page:
                            <span class="font-bold">kraftrack.com/p/{{ slugPreview }}</span>
                            <span class="mt-0.5 block font-medium text-deep/70">
                                If taken, we’ll add a short number so it stays unique.
                            </span>
                        </p>
                    </div>

                    <!-- Step 2: Trade / category + skills -->
                    <div v-else-if="step === 2" class="mt-8 space-y-4">
                        <FormSelect
                            id="job_category"
                            v-model="jobCategory"
                            label="Job category"
                            icon="ti ti-category"
                            placeholder="Select a category"
                            :options="categoryOptions"
                            searchable
                            search-placeholder="Search categories…"
                            :error="localErrors.job_category"
                            @change="onCategoryChange"
                        />

                        <template v-if="jobCategory">
                            <div>
                                <p class="mb-1.5 text-sm font-semibold text-ink/70">
                                    Your trades / specialties
                                </p>
                                <p class="mb-2.5 text-xs font-medium text-ink/40">
                                    Pick up to 12 — clients can find you under each.
                                </p>
                                <FormTextInput
                                    id="trade_search"
                                    v-model="tradeQuery"
                                    type="search"
                                    icon="ti ti-search"
                                    placeholder="Search trades…"
                                    clearable
                                    aria-label="Search trades"
                                />

                                <div class="mt-3">
                                    <FormChoiceGrid
                                        v-model="selectedTrades"
                                        multiple
                                        :max="12"
                                        :options="filteredTrades"
                                        :error="displayError('trades') || displayError('trade')"
                                        :icon-resolver="(label) => tradeIcon(label)"
                                        @change="onTradeChange"
                                    />
                                </div>
                            </div>

                            <FormTextInput
                                v-if="selectedTrades.includes('Other')"
                                id="trade_other"
                                v-model="tradeOther"
                                label="Tell us your other trade"
                                icon="ti ti-briefcase"
                                placeholder="e.g. Solar streetlight installer"
                                :error="localErrors.trade_other"
                                @blur="validateField('trades')"
                            />

                            <FormMultiSelect
                                id="skills"
                                v-model="form.skills"
                                label="Skills"
                                hint="Skills for your selected category only. Up to 15."
                                icon="ti ti-sparkles"
                                placeholder="Search skills related to your craft"
                                :options="skillOptions"
                                :max="15"
                                :error="displayError('skills') || displayError('skills.0')"
                                @change="clearError('skills')"
                            />
                        </template>
                    </div>

                    <!-- Step 3: Location -->
                    <div v-else-if="step === 3" class="mt-8 space-y-4">
                        <FormSelect
                            id="state"
                            v-model="form.state"
                            label="State"
                            icon="ti ti-map-pin"
                            placeholder="Select state"
                            :options="states"
                            searchable
                            search-placeholder="Search states…"
                            :error="displayError('state')"
                            @change="onStateChange"
                        />

                        <FormSelect
                            id="lga"
                            v-model="form.lga"
                            label="Local government (LGA)"
                            icon="ti ti-building-community"
                            :placeholder="form.state ? 'Select LGA' : 'Choose a state first'"
                            :options="lgas"
                            searchable
                            search-placeholder="Search LGAs…"
                            :disabled="!form.state"
                            :error="displayError('lga')"
                            @blur="validateField('lga')"
                        />

                        <FormTextarea
                            id="office_address"
                            v-model="form.office_address"
                            label="Office / workshop address"
                            icon="ti ti-home"
                            placeholder="Street, landmark, or workshop location"
                            :error="displayError('office_address')"
                            @blur="validateField('office_address')"
                        />
                    </div>

                    <!-- Step 4: Contact + password -->
                    <div v-else class="mt-8 space-y-4">
                        <FormTextInput
                            id="whatsapp"
                            v-model="form.whatsapp"
                            type="tel"
                            label="WhatsApp number"
                            icon="ti ti-brand-whatsapp"
                            placeholder="0803 000 0000"
                            autocomplete="tel"
                            inputmode="tel"
                            hint="Used for client review links. Nigerian numbers only."
                            :error="displayError('whatsapp')"
                            @blur="validateField('whatsapp')"
                        />

                        <div>
                            <FormPasswordInput
                                id="password"
                                v-model="form.password"
                                label="Password"
                                placeholder="At least 8 characters"
                                autocomplete="new-password"
                                :error="displayError('password')"
                                @blur="validateField('password')"
                            />
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span
                                    v-for="rule in passwordRules"
                                    :key="rule.key"
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold transition-colors"
                                    :class="rule.ok ? 'bg-emerald-50 text-emerald-700' : 'bg-ink/5 text-ink/40'"
                                >
                                    <i :class="rule.ok ? 'ti ti-check' : 'ti ti-circle'" class="text-[10px]" aria-hidden="true" />
                                    {{ rule.label }}
                                </span>
                            </div>
                        </div>

                        <FormPasswordInput
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            label="Confirm password"
                            icon="ti ti-lock-check"
                            placeholder="Repeat your password"
                            autocomplete="new-password"
                            :error="displayError('password_confirmation')"
                            @blur="validateField('password_confirmation')"
                        />

                        <div class="rounded-2xl border border-ink/8 bg-gradient-to-br from-tint/80 to-pale px-4 py-3.5">
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white text-base shadow-sm">
                                    <i class="ti ti-chart-donut-3" aria-hidden="true" />
                                </span>
                                <div>
                                    <p class="text-sm font-bold text-ink">Profile starts at ~45%</p>
                                    <p class="mt-0.5 text-xs font-medium leading-relaxed text-ink/55">
                                        After signup we’ll gently nudge you to add a photo, bio, and more — so clients see a complete page.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>

            <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
                <FormButton
                    v-if="step > 1"
                    variant="secondary"
                    icon-left="ti ti-arrow-left"
                    label="Back"
                    class="sm:min-w-[7.5rem]"
                    @click="back"
                />

                <FormButton
                    v-if="step < steps.length"
                    variant="primary"
                    block
                    icon-right="ti ti-arrow-right"
                    label="Continue"
                    class="sm:flex-1"
                    @click="next"
                />

                <FormButton
                    v-else
                    variant="accent"
                    block
                    icon-right="ti ti-sparkles"
                    :loading="form.processing"
                    loading-label="Creating account…"
                    label="Create free account"
                    class="sm:flex-1"
                    @click="submit"
                />
            </div>

            <p class="mt-8 text-center text-sm font-medium text-ink/50">
                Already have an account?
                <Link
                    :href="route('login')"
                    class="font-bold text-base transition-colors hover:text-deep"
                >
                    Log in
                </Link>
            </p>
            </template>
        </div>
    </AuthLayout>
</template>

<script setup>
import AppInlineAlert from '@/Components/App/AppInlineAlert.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormChoiceGrid from '@/Components/Form/FormChoiceGrid.vue';
import FormMultiSelect from '@/Components/Form/FormMultiSelect.vue';
import FormPasswordInput from '@/Components/Form/FormPasswordInput.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { tradeIcon } from '@/utils/tradeIcons';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

const props = defineProps({
    trades: {
        type: Array,
        default: () => [],
    },
    jobCategories: {
        type: Object,
        default: () => ({ parents: [], groups: {} }),
    },
    skillCatalog: {
        type: Object,
        default: () => ({ all: [], groups: {} }),
    },
    locations: {
        type: Object,
        default: () => ({}),
    },
    referralCode: {
        type: String,
        default: null,
    },
    pendingVerification: {
        type: Object,
        default: null,
    },
    status: {
        type: String,
        default: null,
    },
});

const verifyStatus = computed(() => props.status || null);

const verifyForm = useForm({ code: '' });
const resendForm = useForm({});
const cooldown = ref(Math.max(0, Number(props.pendingVerification?.resendCooldown) || 0));
let cooldownTimer = null;

const resendLabel = computed(() =>
    cooldown.value > 0 ? `Resend in ${cooldown.value}s` : 'Resend code',
);

watch(
    () => props.pendingVerification,
    (value) => {
        if (value) {
            cooldown.value = Math.max(0, Number(value.resendCooldown) || 0);
            verifyForm.reset();
            verifyForm.clearErrors();
        }
    },
);

const onCodeInput = (value) => {
    verifyForm.code = String(value || '')
        .replace(/\D/g, '')
        .slice(0, 6);
};

const submitVerify = () => {
    verifyForm.post(route('verification.code'), {
        preserveScroll: true,
    });
};

const resendCode = () => {
    if (cooldown.value > 0) return;
    resendForm.post(route('verification.send'), {
        preserveScroll: true,
        onSuccess: () => {
            cooldown.value = 45;
            verifyForm.clearErrors();
        },
    });
};

onMounted(() => {
    cooldownTimer = window.setInterval(() => {
        if (cooldown.value > 0) cooldown.value -= 1;
    }, 1000);
});

onBeforeUnmount(() => {
    if (cooldownTimer) window.clearInterval(cooldownTimer);
});

const steps = [
    {
        key: 'identity',
        eyebrow: 'About you',
        title: 'Let’s start with your name',
        support: 'We’ll use this on your public trust page — plus a business name for your unique URL.',
    },
    {
        key: 'trade',
        eyebrow: 'Your craft',
        title: 'What do you do?',
        support: 'Pick a category, the trades clients should find you under, then skills you’re known for.',
    },
    {
        key: 'location',
        eyebrow: 'Where you work',
        title: 'Where should clients find you?',
        support: 'State, LGA, and your workshop or office address.',
    },
    {
        key: 'secure',
        eyebrow: 'Almost there',
        title: 'Secure your account',
        support: 'WhatsApp for review links, plus a password you’ll remember.',
    },
];

const step = ref(1);
const tradeQuery = ref('');
const tradeOther = ref('');
const jobCategory = ref('');
const selectedTrades = ref([]);
const localErrors = reactive({});

const form = useForm({
    first_name: '',
    last_name: '',
    business_name: '',
    email: '',
    job_category: '',
    trade: '',
    trades: [],
    skills: [],
    state: '',
    lga: '',
    office_address: '',
    whatsapp: '',
    password: '',
    password_confirmation: '',
    ref: props.referralCode || '',
});

const slugPreview = computed(() => {
    const raw = form.business_name.trim().toLowerCase();
    if (!raw) {
        return '';
    }
    return raw
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .replace(/-+/g, '-');
});

const currentStep = computed(() => steps[step.value - 1]);
const progress = computed(() => (step.value / steps.length) * 100);
const states = computed(() => Object.keys(props.locations));
const lgas = computed(() => (form.state ? props.locations[form.state] || [] : []));

const categoryOptions = computed(() => props.jobCategories?.parents || []);

const filteredTrades = computed(() => {
    const q = tradeQuery.value.trim().toLowerCase();
    let source = props.trades;
    if (jobCategory.value && props.jobCategories?.groups?.[jobCategory.value]) {
        source = [...props.jobCategories.groups[jobCategory.value]];
        if (!source.includes('Other')) {
            source.push('Other');
        }
    }
    if (!q) {
        return source;
    }
    return source.filter((t) => t.toLowerCase().includes(q));
});

const skillOptions = computed(() => {
    const group = props.skillCatalog?.groups?.[jobCategory.value] || [];
    if (!group.length) {
        return [];
    }
    const all = props.skillCatalog?.all || [];
    const seen = new Set();
    const options = [];
    for (const skill of group) {
        const match = all.find((s) => s.toLowerCase() === skill.toLowerCase()) || skill;
        const key = match.toLowerCase();
        if (!seen.has(key)) {
            options.push(match);
            seen.add(key);
        }
    }
    return options;
});

watch(skillOptions, (options) => {
    if (!form.skills?.length) {
        return;
    }
    const allowed = new Set(options.map((s) => s.toLowerCase()));
    form.skills = form.skills.filter((s) => allowed.has(String(s).toLowerCase()));
});

const onCategoryChange = () => {
    selectedTrades.value = [];
    tradeOther.value = '';
    tradeQuery.value = '';
    form.skills = [];
    form.job_category = jobCategory.value;
    clearError('trade');
    clearError('trades');
    clearError('trade_other');
    clearError('job_category');
    clearError('skills');
};

watch(
    () => form.errors,
    (errors) => {
        const keys = Object.keys(errors);
        if (!keys.length) {
            return;
        }
        restoreOtherTradeUi();
        if (['first_name', 'last_name', 'email', 'business_name'].some((k) => keys.includes(k))) {
            step.value = 1;
        } else if (
            keys.some((k) => k === 'trade' || k === 'trades' || k.startsWith('trades.') || k === 'skills' || k.startsWith('skills.') || k === 'job_category')
        ) {
            step.value = 2;
        } else if (['state', 'lga', 'office_address'].some((k) => keys.includes(k))) {
            step.value = 3;
        } else {
            step.value = 4;
        }
    },
);

const resolveTradesForSubmit = () => {
    const trades = [...selectedTrades.value];
    if (trades.includes('Other')) {
        const custom = tradeOther.value.trim();
        return trades
            .filter((t) => t !== 'Other')
            .concat(custom ? [custom] : [])
            .filter(Boolean);
    }
    return trades;
};

const restoreOtherTradeUi = () => {
    const known = new Set(props.trades.map((t) => t.toLowerCase()));
    const customs = (form.trades || []).filter((t) => !known.has(String(t).toLowerCase()) && t !== 'Other');
    if (customs.length) {
        tradeOther.value = customs[0];
        selectedTrades.value = [
            ...(form.trades || []).filter((t) => known.has(String(t).toLowerCase())),
            'Other',
        ];
    } else if (Array.isArray(form.trades) && form.trades.length) {
        selectedTrades.value = [...form.trades];
    }
};

const clearError = (field) => {
    delete localErrors[field];
};

const displayError = (field) => localErrors[field] || form.errors[field] || '';

const isEmail = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim());

const isWhatsapp = (value) => {
    const cleaned = value.replace(/\s+/g, '');
    return /^(?:\+?234|0)[789][01]\d{8}$/.test(cleaned);
};

const validateField = (field) => {
    clearError(field);
    if (field === 'trades' || field === 'trade') {
        clearError('trade_other');
        clearError('trades');
        clearError('trade');
    }

    if (field === 'first_name' && !form.first_name.trim()) {
        localErrors.first_name = 'First name is required.';
    }
    if (field === 'last_name' && !form.last_name.trim()) {
        localErrors.last_name = 'Last name is required.';
    }
    if (field === 'email') {
        if (!form.email.trim()) {
            localErrors.email = 'Email is required.';
        } else if (!isEmail(form.email)) {
            localErrors.email = 'Enter a valid email address.';
        }
    }
    if (field === 'business_name') {
        if (!form.business_name.trim()) {
            localErrors.business_name = 'Business name is required for your public URL.';
        } else if (!slugPreview.value) {
            localErrors.business_name = 'Use letters or numbers so we can build a URL.';
        }
    }
    if (field === 'trades' || field === 'trade') {
        if (!selectedTrades.value.length) {
            localErrors.trades = 'Select at least one trade or specialty.';
        } else if (selectedTrades.value.includes('Other') && !tradeOther.value.trim()) {
            localErrors.trade_other = 'Tell us what you do.';
        }
    }
    if (field === 'state' && !form.state) {
        localErrors.state = 'Select your state.';
    }
    if (field === 'lga' && !form.lga) {
        localErrors.lga = 'Select your local government.';
    }
    if (field === 'office_address' && !form.office_address.trim()) {
        localErrors.office_address = 'Add your office or workshop address.';
    }
    if (field === 'whatsapp') {
        if (!form.whatsapp.trim()) {
            localErrors.whatsapp = 'WhatsApp number is required.';
        } else if (!isWhatsapp(form.whatsapp)) {
            localErrors.whatsapp = 'Enter a valid Nigerian WhatsApp number.';
        }
    }
    if (field === 'password') {
        if (form.password.length < 8) {
            localErrors.password = 'Password must be at least 8 characters.';
        }
    }
    if (field === 'password_confirmation') {
        if (form.password_confirmation !== form.password) {
            localErrors.password_confirmation = 'Passwords do not match.';
        }
    }

    return !localErrors[field] && !localErrors.trade_other && !localErrors.trades;
};

const validateStep = (n) => {
    if (n === 1) {
        return ['first_name', 'last_name', 'email', 'business_name'].every((f) => validateField(f));
    }
    if (n === 2) {
        if (!jobCategory.value && (props.jobCategories?.parents || []).length) {
            localErrors.job_category = 'Select a job category.';
            return false;
        }
        clearError('job_category');
        return validateField('trades') && !localErrors.trade_other;
    }
    if (n === 3) {
        return ['state', 'lga', 'office_address'].every((f) => validateField(f));
    }
    if (n === 4) {
        return ['whatsapp', 'password', 'password_confirmation'].every((f) => validateField(f));
    }
    return true;
};

const onTradeChange = () => {
    clearError('trade');
    clearError('trades');
    clearError('trade_other');
    if (!selectedTrades.value.includes('Other')) {
        tradeOther.value = '';
    }
};

const onStateChange = () => {
    form.lga = '';
    clearError('state');
    clearError('lga');
};

const goTo = (n) => {
    if (n < step.value) {
        step.value = n;
    }
};

const next = () => {
    if (!validateStep(step.value)) {
        return;
    }
    step.value = Math.min(step.value + 1, steps.length);
};

const back = () => {
    step.value = Math.max(step.value - 1, 1);
};

const submit = () => {
    if (!validateStep(4)) {
        return;
    }

    const trades = resolveTradesForSubmit();
    if (!trades.length) {
        step.value = 2;
        localErrors.trades = 'Select at least one trade or specialty.';
        return;
    }

    form.job_category = jobCategory.value;
    form.trades = trades;
    form.trade = trades[0];
    form.whatsapp = form.whatsapp.replace(/\s+/g, '');

    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
        onError: () => restoreOtherTradeUi(),
    });
};

const passwordRules = computed(() => [
    { key: 'len', label: '8+ chars', ok: form.password.length >= 8 },
    { key: 'letter', label: 'A letter', ok: /[A-Za-z]/.test(form.password) },
    { key: 'number', label: 'A number', ok: /\d/.test(form.password) },
]);

watch(
    () => form.password,
    () => {
        if (form.password) {
            validateField('password');
        }
    },
);
</script>

<style scoped>
.auth-enter {
    animation: auth-rise 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes auth-rise {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.step-enter-active,
.step-leave-active {
    transition: opacity 0.22s ease, transform 0.22s ease;
}

.step-enter-from {
    opacity: 0;
    transform: translateX(12px);
}

.step-leave-to {
    opacity: 0;
    transform: translateX(-10px);
}
</style>
