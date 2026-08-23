<template>
    <AuthLayout
        headline="Choose a new password."
        support="You’re almost back in. Set a strong password and keep building the record that wins your next client."
        :points="[
            'Use at least 8 characters',
            'Your jobs and reviews stay exactly as they are',
            'You’ll land on your dashboard after saving',
        ]"
    >
        <Head title="Reset password" />

        <div class="auth-enter">
            <h1 class="text-center font-display text-3xl font-extrabold tracking-tight text-ink sm:text-[2.1rem]">
                Set a new password
            </h1>
            <p class="mt-3 text-center text-sm font-semibold leading-relaxed text-ink/55">
                Enter a new password for
                <span class="font-bold text-ink/75">{{ email }}</span>.
            </p>

            <form class="mt-8 space-y-5" @submit.prevent="submit">
                <FormTextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    label="Email address"
                    icon="ti ti-mail"
                    autocomplete="username"
                    required
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
                    :error="form.errors.password"
                />

                <FormPasswordInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    label="Confirm password"
                    placeholder="Repeat your new password"
                    autocomplete="new-password"
                    required
                    :error="form.errors.password_confirmation"
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
                <Link
                    :href="route('login')"
                    class="font-bold text-base transition-colors hover:text-deep"
                >
                    Back to log in
                </Link>
            </p>
        </div>
    </AuthLayout>
</template>

<script setup>
import FormButton from '@/Components/Form/FormButton.vue';
import FormPasswordInput from '@/Components/Form/FormPasswordInput.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
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
