<template>
    <section class="rounded-2xl bg-white px-5 py-5 shadow-premium ring-1 ring-ink/[0.05] sm:px-6">
        <h2 class="text-[15px] font-bold tracking-tight text-ink">Your duty</h2>
        <dl class="mt-4 space-y-3">
            <div
                v-for="row in rows"
                :key="row.label"
                class="flex items-baseline justify-between gap-4"
            >
                <dt class="text-[13px] font-medium text-ink/40">{{ row.label }}</dt>
                <dd class="font-editorial text-[1.05rem] font-semibold tracking-tight text-ink">
                    {{ row.value }}
                </dd>
            </div>
        </dl>
    </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    duty: { type: Object, default: null },
});

const rows = computed(() => props.duty?.rows || []);
</script>
