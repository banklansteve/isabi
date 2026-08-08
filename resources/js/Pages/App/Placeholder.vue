<template>
    <Head :title="title" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-2xl">
            <AppPageHeader
                :title="title"
                :description="summary"
                :back-href="route('dashboard')"
                back-label="Home"
            />

            <div class="rounded-[1.75rem] bg-white p-6 shadow-premium ring-1 ring-ink/[0.06] sm:p-10">
                <ul v-if="highlights?.length" class="space-y-3">
                    <li
                        v-for="item in highlights"
                        :key="item"
                        class="flex items-start gap-3 text-sm font-semibold text-ink/70"
                    >
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-pale text-base">
                            <i class="ti ti-check text-sm" aria-hidden="true" />
                        </span>
                        {{ item }}
                    </li>
                </ul>

                <div class="mt-8 flex flex-wrap gap-3" :class="{ 'mt-0': !highlights?.length }">
                    <Link
                        v-if="cta"
                        :href="cta.href"
                        class="tap-target inline-flex items-center gap-2 rounded-2xl bg-base-action px-5 py-3 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                    >
                        {{ cta.label }}
                        <i class="ti ti-arrow-right" aria-hidden="true" />
                    </Link>
                    <Link
                        :href="route('dashboard')"
                        class="tap-target inline-flex items-center gap-2 rounded-2xl border border-ink/10 bg-white px-5 py-3 text-sm font-bold text-ink/70 transition-colors hover:border-ink/20 hover:text-ink"
                    >
                        Back to home
                    </Link>
                </div>

                <p class="mt-8 text-xs font-medium text-ink/35">
                    This area is scaffolding — full screens will land here next.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AppPageHeader from '@/Components/AppPageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    title: { type: String, required: true },
    eyebrow: { type: String, default: '' },
    summary: { type: String, default: '' },
    icon: { type: String, default: 'ti ti-apps' },
    highlights: { type: Array, default: () => [] },
    cta: { type: Object, default: null },
});
</script>
