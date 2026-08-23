<template>
    <AuthLayout
        tone="admin"
        headline="Set up your account."
        support="You’ve been invited to the Isabi operations team. Choose a password to continue."
        :points="[
            'This invite expires soon — don’t wait',
            'You’ll be signed in right after',
            'A Super Admin can change your roles anytime',
        ]"
    >
        <Head title="Set up your account" />

        <div class="auth-enter">
            <h1 class="text-center text-3xl font-semibold tracking-tight text-ink sm:text-[2.1rem]">
                Set up your account
            </h1>
            <p class="mt-3 text-center text-sm font-semibold leading-relaxed text-ink/55">
                Finish setup for
                <span class="font-bold text-ink/75">{{ email }}</span>.
                This invite expires {{ expires_at ? `on ${expires_at}` : 'soon' }}.
            </p>
            <p v-if="suggested_role" class="mt-2 text-center text-[13px] font-medium text-ink/40">
                Suggested role: {{ suggested_role }} — applied when you accept.
            </p>

            <form class="mt-8 space-y-5" @submit.prevent="submit">
                <FormTextInput
                    v-if="needs_name"
                    id="name"
                    v-model="form.name"
                    label="Your name"
                    icon="ti ti-user"
                    placeholder="First and last name"
                    autocomplete="name"
                    required
                    autofocus
                    :error="form.errors.name"
                />

                <FormPasswordInput
                    id="password"
                    v-model="form.password"
                    label="Password"
                    placeholder="At least 8 characters"
                    autocomplete="new-password"
                    required
                    :autofocus="!needs_name"
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
                    label="Create account"
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
    token: { type: String, required: true },
    email: { type: String, default: '' },
    name: { type: String, default: '' },
    needs_name: { type: Boolean, default: false },
    expires_at: { type: String, default: '' },
    suggested_role: { type: String, default: '' },
});

const form = useForm({
    name: props.name || '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('admin.invite.accept', props.token));
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
