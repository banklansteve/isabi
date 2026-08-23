<template>
    <AuthLayout
        headline="A quick security check."
        support="This area needs your password again before you continue — it keeps your page and client data safe."
        :points="[
            'Confirms it’s really you',
            'Takes a few seconds',
            'Then you’re straight back to what you were doing',
        ]"
    >
        <Head title="Confirm password" />

        <div class="auth-enter">
            <h1 class="text-center font-display text-3xl font-extrabold tracking-tight text-ink sm:text-[2.1rem]">
                Confirm your password
            </h1>
            <p class="mt-3 text-center text-sm font-semibold leading-relaxed text-ink/55">
                Enter your current password to unlock this step.
            </p>

            <form class="mt-8 space-y-5" @submit.prevent="submit">
                <FormPasswordInput
                    id="password"
                    v-model="form.password"
                    label="Password"
                    placeholder="Your current password"
                    autocomplete="current-password"
                    required
                    autofocus
                    :error="form.errors.password"
                />

                <FormButton
                    type="submit"
                    variant="primary"
                    block
                    icon-right="ti ti-lock-open"
                    :loading="form.processing"
                    loading-label="Checking…"
                    label="Confirm and continue"
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

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
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
