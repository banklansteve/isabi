<template>
    <Head title="Application received" />

    <div class="min-h-dvh bg-pale text-ink">
        <PublicTopBar :can-login="canLogin" :can-register="canRegister" />

        <main class="mx-auto max-w-lg px-4 py-16 text-center sm:px-8 sm:py-24">
            <span
                class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600"
            >
                <i class="ti ti-check text-2xl" aria-hidden="true" />
            </span>
            <h1 class="mt-6 font-editorial text-[1.85rem] font-semibold tracking-tight text-ink">
                Application received
            </h1>
            <p class="mt-3 text-sm font-medium leading-relaxed text-ink/55">
                Thanks for applying for
                <span class="font-bold text-ink">{{ vacancy.title }}</span>.
                We’ll review your materials and follow up by email if there’s a fit.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <Link
                    :href="route('careers')"
                    class="tap-target inline-flex items-center justify-center rounded-2xl bg-base-action px-5 py-3 text-sm font-bold text-white hover:bg-base-hover"
                >
                    Back to careers
                </Link>
            </div>
        </main>

        <SiteFooter :can-register="canRegister" />
    </div>
</template>

<script setup>
import PublicTopBar from '@/Components/Marketing/PublicTopBar.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: { type: Boolean, default: true },
    canRegister: { type: Boolean, default: true },
    vacancy: { type: Object, required: true },
});
</script>
