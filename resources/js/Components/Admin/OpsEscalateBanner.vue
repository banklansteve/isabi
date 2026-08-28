<template>
    <section
        v-if="escalate?.href"
        class="flex flex-col gap-4 rounded-2xl bg-white px-5 py-4 shadow-premium ring-1 ring-ink/[0.05] sm:flex-row sm:items-center sm:gap-6 sm:px-6 sm:py-5"
    >
        <span
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-pale text-ink/40"
        >
            <i class="ti ti-circles text-xl" aria-hidden="true" />
        </span>
        <div class="min-w-0 flex-1">
            <p class="text-[15px] font-bold tracking-tight text-ink">Refer a case to admin</p>
            <p class="mt-1 text-[13px] font-medium leading-relaxed text-ink/45">
                Send anything you can’t resolve up for review — case, patrol incident, or moderation flag.
            </p>
        </div>
        <Link
            :href="escalate.href"
            class="inline-flex min-h-[2.75rem] shrink-0 items-center justify-center rounded-xl bg-base-action px-4 text-[13px] font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.45)] transition-colors hover:bg-base-hover active:bg-deep"
        >
            {{ escalate.label || 'Escalate a case' }}
        </Link>
    </section>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    escalate: { type: Object, default: null },
});
</script>
