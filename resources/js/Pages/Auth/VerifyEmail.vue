<template>
    <AuthLayout
        headline="One click to unlock your page."
        support="We sent a verification link to your email. Confirm it so clients can trust that this Kraftrack page is really yours."
        :points="[
            'Keeps fake accounts off the platform',
            'Takes seconds once the email arrives',
            'You can resend the link anytime',
        ]"
    >
        <Head title="Verify email" />

        <div class="auth-enter">
            <h1 class="text-center font-display text-3xl font-extrabold tracking-tight text-ink sm:text-[2.1rem]">
                Verify your email
            </h1>
            <p class="mt-3 text-center text-sm font-semibold leading-relaxed text-ink/55">
                Check your inbox for a link from Kraftrack. Didn’t get it? Resend below.
            </p>

            <div
                v-if="verificationLinkSent"
                class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            >
                A new verification link has been sent to the email on your account.
            </div>

            <form class="mt-8 space-y-4" @submit.prevent="submit">
                <FormButton
                    type="submit"
                    variant="primary"
                    block
                    icon-left="ti ti-mail-forward"
                    :loading="form.processing"
                    loading-label="Sending…"
                    label="Resend verification email"
                />
            </form>

            <p class="mt-8 text-center text-sm font-medium text-ink/50">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="font-bold text-base transition-colors hover:text-deep"
                >
                    Log out
                </Link>
            </p>
        </div>
    </AuthLayout>
</template>

<script setup>
import FormButton from '@/Components/Form/FormButton.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, default: '' },
});

const form = useForm({});

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);

const submit = () => {
    form.post(route('verification.send'));
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
