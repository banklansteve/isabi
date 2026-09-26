<template>
    <AuthLayout
        headline="We’ll get you back in."
        support="Enter the email on your account and we’ll send a secure reset link — your jobs and reviews stay exactly as they are."
        :points="[
            'Link expires in about an hour',
            'Your work log and reviews stay safe',
            'Same email you used to register',
        ]"
    >
        <Head title="Forgot password" />

        <div class="auth-enter">
            <template v-if="sent">
                <div class="text-center">
                    <span
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/80"
                    >
                        <i class="ti ti-mail-check text-2xl" aria-hidden="true" />
                    </span>
                    <h1 class="mt-5 font-display text-3xl font-extrabold tracking-tight text-ink sm:text-[2.1rem]">
                        Check your email
                    </h1>
                    <p class="mt-3 text-sm font-semibold leading-relaxed text-ink/55">
                        If an account exists for
                        <span class="font-bold text-ink">{{ sentTo || form.email }}</span>,
                        we’ve sent a link to choose a new password.
                    </p>
                </div>

                <AppInlineAlert
                    class="mt-6"
                    tone="info"
                    title="Didn’t get it?"
                    message="Check spam, wait a minute, then try again. Links expire for security — request a fresh one if needed."
                />

                <div class="mt-8 space-y-3">
                    <FormButton
                        type="button"
                        variant="secondary"
                        block
                        label="Send another link"
                        @click="resetSent"
                    />
                    <p class="text-center text-sm font-medium text-ink/50">
                        <Link
                            :href="route('login')"
                            class="font-bold text-base transition-colors hover:text-deep"
                        >
                            Back to log in
                        </Link>
                    </p>
                </div>
            </template>

            <template v-else>
                <h1 class="text-center font-display text-3xl font-extrabold tracking-tight text-ink sm:text-[2.1rem]">
                    Forgot your password?
                </h1>
                <p class="mt-3 text-center text-sm font-semibold leading-relaxed text-ink/55">
                    Enter your email and we’ll send a link to choose a new one.
                </p>

                <AppInlineAlert
                    v-if="form.errors.email"
                    class="mt-6"
                    tone="error"
                    title="Couldn’t send"
                    :message="form.errors.email"
                />

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
            </template>
        </div>
    </AuthLayout>
</template>

<script setup>
import AppInlineAlert from '@/Components/App/AppInlineAlert.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    status: {
        type: String,
        default: '',
    },
    sentTo: {
        type: String,
        default: '',
    },
});

const dismissSent = ref(false);
const sent = computed(
    () =>
        !dismissSent.value &&
        (props.status === 'reset-link-sent' || props.status === 'passwords.sent'),
);

const form = useForm({
    email: props.sentTo || '',
});

watch(
    () => props.sentTo,
    (value) => {
        if (value && !form.email) {
            form.email = value;
        }
    },
);

const submit = () => {
    dismissSent.value = false;
    form.post(route('password.email'), {
        preserveScroll: true,
    });
};

const resetSent = () => {
    dismissSent.value = true;
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
