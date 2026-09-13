<template>
    <AuthLayout
        tone="admin"
        headline="Confirm it’s you."
        support="If you still have a 6-digit code from an older invite, enter it here. New invites use an email link instead."
        :points="[
            'Prefer the link in your invite email',
            'Expired invites need a Super Admin',
            'Nobody can sign in until setup is done',
        ]"
    >
        <Head title="Confirm your email" />

        <div class="auth-enter">
            <h1 class="text-center text-3xl font-semibold tracking-tight text-ink sm:text-[2.1rem]">
                Enter your code
            </h1>
            <p class="mt-3 text-center text-sm font-semibold leading-relaxed text-ink/55">
                New invites arrive as a setup link, not a code. If your link expired, ask a Super Admin to send a new one — you cannot resend it yourself.
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
                    :error="form.errors.email"
                />

                <FormTextInput
                    id="code"
                    v-model="form.code"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    label="6-digit code"
                    icon="ti ti-key"
                    placeholder="000000"
                    maxlength="6"
                    required
                    autofocus
                    :error="form.errors.code"
                />

                <FormButton
                    type="submit"
                    variant="primary"
                    block
                    icon-right="ti ti-arrow-right"
                    :loading="form.processing"
                    loading-label="Checking…"
                    label="Confirm email"
                />
            </form>

            <p class="mt-6 text-center text-sm font-medium text-ink/45">
                Need a new invite?
                <Link :href="route('admin.login')" class="font-bold text-base hover:text-deep">
                    Contact a Super Admin
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

const props = defineProps({
    email: {
        type: String,
        default: '',
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: props.email || '',
    code: '',
});

const submit = () => {
    form.post(route('admin.invite.verify'));
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
