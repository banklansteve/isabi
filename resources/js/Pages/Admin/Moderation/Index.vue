<template>
    <Head title="Moderation" />

    <AdminChrome title="Moderation" />
        <div class="rounded-[1.5rem] bg-white p-6 shadow-premium ring-1 ring-ink/[0.06] sm:p-8">
            <h2 class="font-display text-2xl font-bold tracking-tight text-ink">
                Content moderation
            </h2>
            <p class="mt-3 max-w-2xl text-sm font-medium leading-relaxed text-ink/60">
                Review reported jobs, reviews, and profiles. Tools come next.
            </p>
        </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head } from '@inertiajs/vue3';
</script>
