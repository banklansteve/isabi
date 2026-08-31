<template>
    <Head title="Quote expired" />

    <div class="min-h-dvh bg-pale font-app text-ink antialiased">
        <main class="mx-auto flex min-h-dvh max-w-lg items-center px-4 py-12">
            <div class="w-full rounded-[1.75rem] bg-white p-8 text-center shadow-premium ring-1 ring-ink/[0.06]">
                <h1 class="font-editorial text-2xl font-semibold text-ink">This quote link has expired</h1>
                <p class="mt-2 text-sm font-medium text-ink/50">
                    Contact {{ businessName || 'the artisan' }} directly if you still need a quote.
                </p>
            </div>
        </main>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    businessName: { type: String, default: '' },
});
</script>
