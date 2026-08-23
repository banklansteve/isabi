<template>
    <AuthLayout
        tone="admin"
        headline="This invite isn’t valid."
        support="The link may have expired, already been used, or been replaced by a newer invite."
        :points="[
            'Invites expire after a short window',
            'Only a Super Admin can send a new one',
            'You cannot request a resend from here',
        ]"
    >
        <Head title="Invite expired" />

        <div class="auth-enter">
            <h1 class="text-center text-3xl font-semibold tracking-tight text-ink sm:text-[2.1rem]">
                This invite has expired
            </h1>
            <p class="mt-3 text-center text-sm font-semibold leading-relaxed text-ink/55">
                Ask a Super Admin to send you a new invitation. For security, you cannot request a new link yourself.
            </p>

            <div class="mt-8">
                <Link
                    :href="route('admin.login')"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl bg-base-action px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-base-hover"
                >
                    Back to admin log in
                    <i class="ti ti-arrow-right" aria-hidden="true" />
                </Link>
            </div>
        </div>
    </AuthLayout>
</template>

<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
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
