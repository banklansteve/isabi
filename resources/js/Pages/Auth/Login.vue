<template>
    <AuthLayout
        headline="Welcome back. Your reputation is waiting."
        support="Sign in to log new work, send review requests, and keep building the record that wins your next client."
        :points="[
            'Pick up right where you left off',
            'Log a finished job in under a minute',
            'Watch reviews land as clients respond',
        ]"
    >
        <Head title="Log in" />

        <div class="auth-enter">
            <h1 class="text-center font-display text-3xl font-extrabold tracking-tight text-ink sm:text-[2.1rem]">
                Log in to Isabi
            </h1>
            <p class="mt-3 text-center text-sm font-semibold leading-relaxed text-ink/55">
                Enter your email and password to continue.
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
                    :error="emailError"
                    @blur="touched.email = true"
                />

                <FormPasswordInput
                    id="password"
                    v-model="form.password"
                    label="Password"
                    placeholder="Your password"
                    autocomplete="current-password"
                    required
                    :error="form.errors.password"
                >
                    <template v-if="canResetPassword" #action>
                        <Link
                            :href="route('password.request')"
                            class="text-xs font-semibold text-base transition-colors hover:text-deep"
                        >
                            Forgot password?
                        </Link>
                    </template>
                </FormPasswordInput>

                <FormCheckbox v-model="form.remember" name="remember">
                    Remember me on this device
                </FormCheckbox>

                <FormButton
                    type="submit"
                    variant="primary"
                    block
                    icon-right="ti ti-arrow-right"
                    :loading="form.processing"
                    loading-label="Signing in…"
                    label="Log in"
                />
            </form>

            <p class="mt-8 text-center text-sm font-medium text-ink/50">
                New to Isabi?
                <Link
                    :href="route('register')"
                    class="font-bold text-base transition-colors hover:text-deep"
                >
                    Create a free account
                </Link>
            </p>
        </div>
    </AuthLayout>
</template>

<script setup>
import FormButton from '@/Components/Form/FormButton.vue';
import FormCheckbox from '@/Components/Form/FormCheckbox.vue';
import FormPasswordInput from '@/Components/Form/FormPasswordInput.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const touched = reactive({ email: false });

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const emailValid = computed(() =>
    /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim()),
);

const emailError = computed(() => {
    if (form.errors.email) {
        return form.errors.email;
    }
    if (touched.email && form.email && !emailValid.value) {
        return 'Enter a valid email address.';
    }
    return '';
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
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
