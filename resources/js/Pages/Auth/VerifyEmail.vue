<template>
    <AuthLayout
        headline="Confirm it’s really you."
        support="We sent a 6-digit code to your inbox. Enter it here — no need to leave this tab."
        :points="[
            'Code expires in about 15 minutes',
            'Resend anytime after a short wait',
            'You can still explore the app while unverified',
        ]"
    >
        <Head title="Verify email" />

        <div class="auth-enter">
            <h1 class="text-center font-display text-3xl font-extrabold tracking-tight text-ink sm:text-[2.1rem]">
                Enter your code
            </h1>
            <p class="mt-3 text-center text-sm font-semibold leading-relaxed text-ink/55">
                We sent a 6-digit code to
                <span class="font-bold text-ink">{{ email }}</span>.
            </p>

            <AppInlineAlert
                v-if="status === 'verification-link-sent'"
                class="mt-6"
                tone="success"
                title="New code sent"
                message="Check your inbox (and spam) for a fresh 6-digit code."
            />

            <AppInlineAlert
                v-if="form.errors.code || form.errors.email || form.errors.link"
                class="mt-6"
                tone="error"
                title="Couldn’t verify"
                :message="form.errors.code || form.errors.email || form.errors.link"
            />

            <form class="mt-8 space-y-5" @submit.prevent="submitCode">
                <FormTextInput
                    id="code"
                    :model-value="form.code"
                    label="Verification code"
                    icon="ti ti-password"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    placeholder="6-digit code"
                    maxlength="6"
                    :error="form.errors.code"
                    @update:model-value="onCodeInput"
                />

                <FormButton
                    type="submit"
                    variant="primary"
                    block
                    label="Verify &amp; continue"
                    :loading="form.processing"
                    loading-label="Verifying…"
                    icon-right="ti ti-check"
                />
            </form>

            <div class="mt-6 rounded-2xl bg-pale/80 px-4 py-3.5 ring-1 ring-ink/[0.06]">
                <p class="text-sm font-medium text-ink/60">
                    Code expires in about {{ codeTtlMinutes }} minutes. Prefer the email button?
                    It works too — if it opens in another window, come back here after it confirms.
                </p>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <FormButton
                        type="button"
                        variant="secondary"
                        :label="resendLabel"
                        :disabled="cooldown > 0 || resendForm.processing"
                        :loading="resendForm.processing"
                        loading-label="Sending…"
                        @click="resend"
                    />
                    <Link
                        :href="route('dashboard')"
                        class="text-sm font-bold text-base-action hover:text-base-hover"
                    >
                        Continue to dashboard
                    </Link>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>

<script setup>
import AppInlineAlert from '@/Components/App/AppInlineAlert.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    status: { type: String, default: null },
    email: { type: String, default: '' },
    resendCooldown: { type: Number, default: 0 },
    codeTtlMinutes: { type: Number, default: 15 },
});

const form = useForm({
    code: '',
});

const resendForm = useForm({});
const cooldown = ref(Math.max(0, Number(props.resendCooldown) || 0));
let timer = null;

const resendLabel = computed(() =>
    cooldown.value > 0 ? `Resend in ${cooldown.value}s` : 'Resend code',
);

const onCodeInput = (value) => {
    form.code = String(value || '')
        .replace(/\D/g, '')
        .slice(0, 6);
};

const submitCode = () => {
    form.post(route('verification.code'), {
        preserveScroll: true,
    });
};

const resend = () => {
    if (cooldown.value > 0) return;
    resendForm.post(route('verification.send'), {
        preserveScroll: true,
        onSuccess: () => {
            cooldown.value = 45;
        },
    });
};

onMounted(() => {
    timer = window.setInterval(() => {
        if (cooldown.value > 0) {
            cooldown.value -= 1;
        }
    }, 1000);
});

onBeforeUnmount(() => {
    if (timer) window.clearInterval(timer);
});
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
</style>
