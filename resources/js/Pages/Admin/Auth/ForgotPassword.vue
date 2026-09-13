<template>
    <AuthLayout
        tone="admin"
        headline="We’ll get you back in."
        support="Enter the email on your operations account and we’ll send a reset link."
        :points="[
            'Staff accounts only',
            'The link expires after a short window',
            'You’ll land on the admin log in after saving',
        ]"
    >
        <Head title="Admin forgot password" />

        <div class="auth-enter">
            <h1 class="text-center text-3xl font-semibold tracking-tight text-ink sm:text-[2.1rem]">
                Forgot your password?
            </h1>
            <p class="mt-3 text-center text-sm font-semibold leading-relaxed text-ink/55">
                We’ll email a reset link if this is an active staff account.
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
                    label="Work email"
                    icon="ti ti-mail"
                    placeholder="you@kraftrack.test"
                    autocomplete="username"
                    required
                    autofocus
                    :error="form.errors.email"
                />

                <FormButton
                    type="submit"
                    variant="primary"
                    block
                    :loading="form.processing"
                    loading-label="Sending…"
                    label="Send reset link"
                />
            </form>
        </div>
    </AuthLayout>
</template>

<script setup>
import FormButton from '@/Components/Form/FormButton.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('admin.password.email'));
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
