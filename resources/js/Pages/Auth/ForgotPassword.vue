<template>
    <AuthLayout
        headline="We’ll get you back in."
        support="Enter the email on your account and we’ll send a reset link — no stress."
        :points="[
            'Reset takes under a minute',
            'Your jobs and reviews stay safe',
            'Same email you used to register',
        ]"
    >
        <Head title="Forgot password" />

        <div class="auth-enter">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-base">
                Password reset
            </p>
            <h1 class="mt-2 font-display text-3xl font-extrabold tracking-tight text-ink sm:text-[2rem]">
                Forgot your password?
            </h1>
            <p class="mt-2 text-sm font-medium leading-relaxed text-ink/55">
                No problem. Enter your email and we’ll send a link to choose a new one.
            </p>

            <div
                v-if="status"
                class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            >
                {{ status }}
            </div>

            <form class="mt-8 space-y-5" @submit.prevent="submit">
                <FormTextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    label="Email address"
                    icon="ti ti-mail"
                    placeholder="you@example.com"
                    autocomplete="username"
                    required
                    autofocus
                    :error="form.errors.email"
                />

                <FormButton
                    type="submit"
                    variant="primary"
                    block
                    icon-right="ti ti-send"
                    :loading="form.processing"
                    loading-label="Sending…"
                    label="Email reset link"
                />
            </form>

            <p class="mt-8 text-center text-sm font-medium text-ink/50">
                Remembered it?
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
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
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
