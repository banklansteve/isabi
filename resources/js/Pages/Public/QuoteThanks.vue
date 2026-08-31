<template>
    <Head title="Thanks" />

    <div class="min-h-dvh bg-pale font-app text-ink antialiased">
        <main class="mx-auto flex min-h-dvh max-w-lg items-center px-4 py-12">
            <div class="w-full overflow-hidden rounded-[1.75rem] bg-white p-8 text-center shadow-premium ring-1 ring-ink/[0.06]">
                <span
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl"
                    :class="decision === 'accepted' ? 'bg-emerald-50 text-emerald-700' : 'bg-pale text-ink/45'"
                >
                    <i :class="decision === 'accepted' ? 'ti ti-check text-3xl' : 'ti ti-x text-3xl'" aria-hidden="true" />
                </span>
                <h1 class="mt-5 font-editorial text-2xl font-semibold tracking-tight text-ink">
                    {{ decision === 'accepted' ? 'Quote accepted' : 'Quote declined' }}
                </h1>
                <p class="mt-2 text-sm font-medium leading-relaxed text-ink/50">
                    {{ businessName }} has been notified. They may reach out to confirm next steps.
                </p>
            </div>
        </main>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    businessName: { type: String, default: '' },
    decision: { type: String, default: 'accepted' },
    clientName: { type: String, default: '' },
});
</script>
