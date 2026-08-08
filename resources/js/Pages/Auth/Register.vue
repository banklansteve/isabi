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
                            <span class="font-bold">isabi.dev/p/{{ slugPreview }}</span>
                            <span class="mt-0.5 block font-medium text-deep/70">
                                If taken, we’ll add a short number so it stays unique.
                            </span>
                        </p>
                    </div>

                    <!-- Step 2: Trade -->
                    <div v-else-if="step === 2" class="mt-8 space-y-4">
                        <FormTextInput
                            id="trade_search"
                            v-model="tradeQuery"
                            type="search"
                            icon="ti ti-search"
                            placeholder="Search trades…"
                            clearable
                            aria-label="Search trades"
                        />

                        <FormChoiceGrid
                            v-model="form.trade"
                            :options="filteredTrades"
                            :error="displayError('trade')"
                            :icon-resolver="(label) => tradeIcon(label)"
                            @change="onTradeChange"
                        />

                        <FormTextInput
                            v-if="form.trade === 'Other'"
                            id="trade_other"
                            v-model="tradeOther"
                            label="Tell us your trade"
                            icon="ti ti-briefcase"
                            placeholder="e.g. Solar streetlight installer"
                            :error="localErrors.trade_other"
                            @blur="validateField('trade')"
                        />
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
        </div>
    </AuthLayout>
</template>

<script setup>
import FormButton from '@/Components/Form/FormButton.vue';
import FormChoiceGrid from '@/Components/Form/FormChoiceGrid.vue';
import FormPasswordInput from '@/Components/Form/FormPasswordInput.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { tradeIcon } from '@/utils/tradeIcons';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    trades: {
        type: Array,
        default: () => [],
    },
    locations: {
        type: Object,
        default: () => ({}),
    },
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
        support: 'Pick the trade clients should find you under.',
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
const localErrors = reactive({});

const form = useForm({
    first_name: '',
    last_name: '',
    business_name: '',
    email: '',
    trade: '',
    state: '',
    lga: '',
    office_address: '',
    whatsapp: '',
    password: '',
    password_confirmation: '',
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

const filteredTrades = computed(() => {
    const q = tradeQuery.value.trim().toLowerCase();
    if (!q) {
        return props.trades;
    }
    return props.trades.filter((t) => t.toLowerCase().includes(q));
});

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
        } else if (keys.includes('trade')) {
            step.value = 2;
        } else if (['state', 'lga', 'office_address'].some((k) => keys.includes(k))) {
            step.value = 3;
        } else {
            step.value = 4;
        }
    },
);

const resolveTradeForSubmit = () => {
    if (form.trade === 'Other') {
        return tradeOther.value.trim();
    }
    return form.trade;
};

const restoreOtherTradeUi = () => {
    if (form.trade && form.trade !== 'Other' && !props.trades.includes(form.trade)) {
        tradeOther.value = form.trade;
        form.trade = 'Other';
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
    if (field === 'trade') {
        clearError('trade_other');
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
    if (field === 'trade') {
        if (!form.trade) {
            localErrors.trade = 'Select your trade or profession.';
        } else if (form.trade === 'Other' && !tradeOther.value.trim()) {
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

    return !localErrors[field] && !localErrors.trade_other;
};

const validateStep = (n) => {
    if (n === 1) {
        return ['first_name', 'last_name', 'email', 'business_name'].every((f) => validateField(f));
    }
    if (n === 2) {
        return validateField('trade') && !localErrors.trade_other;
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
    clearError('trade_other');
    if (form.trade !== 'Other') {
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

    const tradeValue = resolveTradeForSubmit();
    if (!tradeValue) {
        step.value = 2;
        localErrors.trade = 'Select your trade or profession.';
        return;
    }

    form.trade = tradeValue;
    form.whatsapp = form.whatsapp.replace(/\s+/g, '');

    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
        onError: () => restoreOtherTradeUi(),
    });
};
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
