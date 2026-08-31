<template>
    <span v-if="job.removed" class="rounded-full bg-ink/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-ink/50">Removed</span>
    <span v-else-if="job.hidden" class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-800">Hidden</span>
    <span v-if="job.flagged" class="rounded-full bg-coral-tint px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-coral-deep">Flagged</span>
    <span v-if="job.referred" class="rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-violet-700">Referred</span>
</template>

<script setup>
defineProps({
    job: { type: Object, required: true },
});
</script>
