<template>
    <AuthLayout
        headline="Choose a new password."
        support="You’re almost back in. Set a password of at least 8 characters — then sign in and keep building."
        :points="[
            'Use at least 8 characters',
            'This link works once, then expires',
            'Other signed-in devices will be signed out',
        ]"
    >
        <Head title="Reset password" />

        <div class="auth-enter">
            <h1 class="text-center font-display text-3xl font-extrabold tracking-tight text-ink sm:text-[2.1rem]">
                Set a new password
            </h1>
            <p class="mt-3 text-center text-sm font-semibold leading-relaxed text-ink/55">
                Enter a new password for
                <span class="font-bold text-ink">{{ email }}</span>.
            </p>

            <AppInlineAlert
                v-if="form.errors.email || form.errors.token"
                class="mt-6"
                tone="error"
                title="Couldn’t reset"
                :message="form.errors.email || form.errors.token || 'This reset link is invalid or has expired. Request a new one.'"
            />

            <form class="mt-8 space-y-5" @submit.prevent="submit">
                <input type="hidden" name="token" :value="form.token" />

                <FormTextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    label="Email address"
                    icon="ti ti-mail"
                    autocomplete="username"
                    required
                    readonly
                    :error="form.errors.email"
                />

                <FormPasswordInput
                    id="password"
                    v-model="form.password"
                    label="New password"
                    placeholder="At least 8 characters"
                    autocomplete="new-password"
                    required
                    autofocus
                    :error="passwordError"
                    @blur="touched.password = true"
                />

                <FormPasswordInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    label="Confirm password"
                    placeholder="Repeat your new password"
                    autocomplete="new-password"
                    required
                    :error="confirmError"
                    @blur="touched.confirm = true"
                />

                <FormButton
                    type="submit"
                    variant="primary"
                    block
                    icon-right="ti ti-check"
                    :loading="form.processing"
                    loading-label="Saving…"
                    label="Reset password"
                />
            </form>

            <p class="mt-8 text-center text-sm font-medium text-ink/50">
                Link expired?
                <Link
                    :href="route('password.request')"
                    class="font-bold text-base transition-colors hover:text-deep"
                >
                    Request a new one
                </Link>
            </p>
        </div>
    </AuthLayout>
</template>

<script setup>
import AppInlineAlert from '@/Components/App/AppInlineAlert.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormPasswordInput from '@/Components/Form/FormPasswordInput.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const touched = reactive({ password: false, confirm: false });

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const passwordError = computed(() => {
    if (form.errors.password) return form.errors.password;
    if (touched.password && form.password && form.password.length < 8) {
        return 'Use at least 8 characters.';
    }
    return '';
});

const confirmError = computed(() => {
    if (form.errors.password_confirmation) return form.errors.password_confirmation;
    if (
        touched.confirm &&
        form.password_confirmation &&
        form.password_confirmation !== form.password
    ) {
        return 'Those passwords don’t match.';
    }
    return '';
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
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
</style>
