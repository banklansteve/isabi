<template>
    <AuthLayout
        :headline="status === 'verified' ? 'You’re verified.' : 'That link expired.'"
        :support="
            status === 'verified'
                ? 'Your email is confirmed. Head back to Kraftrack and keep building your page.'
                : 'Open Kraftrack and enter the code from your email, or request a fresh one.'
        "
        :points="
            status === 'verified'
                ? [
                      'Review requests are unlocked',
                      'Your account stays signed in on your other tab',
                      'You can close this window anytime',
                  ]
                : [
                      'Codes expire for your security',
                      'Resend from the app in a few seconds',
                      'Prefer the 6-digit code over the link',
                  ]
        "
    >
        <Head :title="status === 'verified' ? 'Email verified' : 'Verification link'" />

        <div class="auth-enter text-center">
            <span
                class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl ring-1"
                :class="
                    status === 'verified'
                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200/80'
                        : 'bg-pale text-ink/45 ring-ink/10'
                "
            >
                <i
                    class="text-2xl"
                    :class="status === 'verified' ? 'ti ti-circle-check' : 'ti ti-link-off'"
                    aria-hidden="true"
                />
            </span>

            <h1 class="mt-5 font-display text-3xl font-extrabold tracking-tight text-ink sm:text-[2.1rem]">
                {{ status === 'verified' ? 'Email verified' : 'Link expired' }}
            </h1>
            <p class="mt-3 text-sm font-semibold leading-relaxed text-ink/55">
                {{ message }}
            </p>

            <div class="mt-8 flex flex-col gap-3">
                <Link
                    v-if="status === 'verified' && dashboardUrl"
                    :href="dashboardUrl"
                    class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-base-action px-5 py-3.5 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.45)] transition-colors hover:bg-base-hover"
                >
                    Open Kraftrack
                    <i class="ti ti-arrow-right text-base" aria-hidden="true" />
                </Link>
                <Link
                    v-else-if="loginUrl"
                    :href="loginUrl"
                    class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-base-action px-5 py-3.5 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.45)] transition-colors hover:bg-base-hover"
                >
                    Log in to continue
                    <i class="ti ti-arrow-right text-base" aria-hidden="true" />
                </Link>
            </div>
        </div>
    </AuthLayout>
</template>

<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    status: { type: String, default: 'verified' },
    message: { type: String, default: '' },
    sameSession: { type: Boolean, default: false },
    dashboardUrl: { type: String, default: '' },
    loginUrl: { type: String, default: '' },
});
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
