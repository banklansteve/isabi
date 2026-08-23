<template>
    <AuthLayout
        tone="admin"
        headline="Choose your password."
        support="Your email is confirmed. Set a password to enter the operations portal."
        :points="[
            'Use at least 8 characters',
            'You’ll be signed in right after',
            'Keep this password to yourself',
        ]"
    >
        <Head title="Set your password" />

        <div class="auth-enter">
            <h1 class="text-center text-3xl font-semibold tracking-tight text-ink sm:text-[2.1rem]">
                Set your password
            </h1>
            <p class="mt-3 text-center text-sm font-semibold leading-relaxed text-ink/55">
                Welcome{{ name ? `, ${name}` : '' }}. Finish setting up
                <span class="font-bold text-ink/75">{{ email }}</span>.
            </p>

            <div
                v-if="status"
                class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            >
                {{ status }}
            </div>

            <form class="mt-8 space-y-5" @submit.prevent="submit">
                <FormPasswordInput
                    id="password"
                    v-model="form.password"
                    label="Password"
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
                    placeholder="Repeat your password"
                    autocomplete="new-password"
                    required
                    :error="form.errors.password_confirmation"
                />

                <FormButton
                    type="submit"
                    variant="primary"
                    block
                    icon-right="ti ti-arrow-right"
                    :loading="form.processing"
                    loading-label="Saving…"
                    label="Save and continue"
                />
            </form>
        </div>
    </AuthLayout>
</template>

<script setup>
import FormButton from '@/Components/Form/FormButton.vue';
import FormPasswordInput from '@/Components/Form/FormPasswordInput.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    email: {
        type: String,
        default: '',
    },
    name: {
        type: String,
        default: '',
    },
    status: {
        type: String,
    },
});

const form = useForm({
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('admin.invite.password'));
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
