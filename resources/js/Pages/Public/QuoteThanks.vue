<template>
    <Head title="Thanks" />

    <div class="min-h-dvh bg-pale font-app text-ink antialiased">
        <main class="mx-auto flex min-h-dvh max-w-lg items-center px-4 py-12">
            <div
                class="w-full overflow-hidden rounded-[1.75rem] bg-white p-8 text-center shadow-premium ring-1 ring-ink/[0.06]"
            >
                <span
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl"
                    :class="iconWrapClass"
                >
                    <i :class="[iconClass, 'text-3xl']" aria-hidden="true" />
                </span>
                <h1 class="mt-5 font-editorial text-2xl font-semibold tracking-tight text-ink">
                    {{ title }}
                </h1>
                <p class="mt-2 text-sm font-medium leading-relaxed text-ink/50">
                    {{ body }}
                </p>
            </div>
        </main>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    businessName: { type: String, default: '' },
    decision: { type: String, default: 'accepted' },
    clientName: { type: String, default: '' },
});

const normalized = computed(() => {
    const value = String(props.decision || '');
    if (value === 'accepted' || value === 'adjustments' || value === 'declined') {
        return value;
    }
    if (value === 'adjustments_requested') {
        return 'adjustments';
    }
    return value === 'declined' ? 'declined' : 'accepted';
});

const title = computed(() => {
    if (normalized.value === 'accepted') {
        return 'Quote accepted';
    }
    if (normalized.value === 'adjustments') {
        return 'Change request sent';
    }
    return 'Quote declined';
});

const body = computed(() => {
    const name = props.businessName || 'The artisan';
    if (normalized.value === 'accepted') {
        return `${name} has been notified. They may reach out to confirm next steps.`;
    }
    if (normalized.value === 'adjustments') {
        return `${name} has your note and can send an updated quote.`;
    }
    return `${name} has been notified of your decision.`;
});

const iconWrapClass = computed(() => {
    if (normalized.value === 'accepted') {
        return 'bg-emerald-50 text-emerald-700';
    }
    if (normalized.value === 'adjustments') {
        return 'bg-tint text-base-action';
    }
    return 'bg-pale text-ink/45';
});

const iconClass = computed(() => {
    if (normalized.value === 'accepted') {
        return 'ti ti-check';
    }
    if (normalized.value === 'adjustments') {
        return 'ti ti-pencil';
    }
    return 'ti ti-x';
});
</script>
