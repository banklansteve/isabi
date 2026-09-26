<template>
    <div class="flex min-h-dvh items-center justify-center bg-pale px-4 py-10 font-app text-ink">
        <Head :title="status === 'verified' ? 'Email verified' : 'Verification link'" />

        <div
            class="w-full max-w-md overflow-hidden rounded-[1.5rem] bg-white p-6 shadow-premium ring-1 ring-ink/[0.06] sm:p-8"
        >
            <div class="flex justify-center">
                <BrandMark variant="solid" class="h-12 w-12" />
            </div>

            <div class="mt-5 text-center">
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl"
                    :class="status === 'verified' ? 'bg-tint text-base-action' : 'bg-pale text-ink/45'"
                >
                    <i
                        class="text-2xl"
                        :class="status === 'verified' ? 'ti ti-circle-check' : 'ti ti-link-off'"
                        aria-hidden="true"
                    />
                </div>
                <h1 class="mt-4 font-editorial text-2xl font-semibold tracking-tight text-ink">
                    {{ status === 'verified' ? 'Email verified' : 'Link expired' }}
                </h1>
                <p class="mt-2 text-sm font-medium leading-relaxed text-ink/55">
                    {{ message }}
                </p>
            </div>

            <div class="mt-6 flex flex-col gap-2">
                <Link
                    v-if="status === 'verified' && dashboardUrl"
                    :href="dashboardUrl"
                    class="tap-target inline-flex items-center justify-center rounded-xl bg-base-action px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-base-hover"
                >
                    Open Kraftrack
                </Link>
                <Link
                    v-else-if="loginUrl"
                    :href="loginUrl"
                    class="tap-target inline-flex items-center justify-center rounded-xl bg-base-action px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-base-hover"
                >
                    Log in to continue
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import BrandMark from '@/Components/BrandMark.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    status: { type: String, default: 'verified' },
    message: { type: String, default: '' },
    sameSession: { type: Boolean, default: false },
    dashboardUrl: { type: String, default: '' },
    loginUrl: { type: String, default: '' },
});
</script>
