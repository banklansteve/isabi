<template>
    <AuthLayout
        tone="admin"
        headline="Choose a new password."
        support="You’re almost back into the operations portal."
        :points="[
            'Use at least 8 characters',
            'This only affects your staff login',
            'You’ll sign in on the admin page after saving',
        ]"
    >
        <Head title="Reset admin password" />

        <div class="auth-enter">
            <h1 class="text-center text-3xl font-semibold tracking-tight text-ink sm:text-[2.1rem]">
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
                    label="Work email"
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
                    :loading="form.processing"
                    loading-label="Saving…"
                    label="Save password"
                />
            </form>
        </div>
    </AuthLayout>
</template>

<script setup>
import FormButton from '@/Components/Form/FormButton.vue';
import FormPasswordInput from '@/Components/Form/FormPasswordInput.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        default: '',
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('admin.password.store'));
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
