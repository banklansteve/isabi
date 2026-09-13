<template>
    <article
        class="flex flex-col gap-3 rounded-2xl bg-white p-3.5 shadow-premium ring-1 ring-ink/[0.05] transition-shadow duration-150 hover:shadow-lg sm:flex-row sm:items-center sm:gap-4 sm:p-4"
    >
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <span
                class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-pale text-[13px] font-bold text-ink/50"
            >
                <img
                    v-if="person.avatar_url"
                    :src="person.avatar_url"
                    :alt="person.name"
                    class="h-full w-full object-cover"
                    loading="lazy"
                />
                <template v-else>{{ initials }}</template>
            </span>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <p class="truncate text-sm font-bold text-ink">{{ person.name }}</p>
                    <span
                        v-for="badge in badges"
                        :key="badge.label"
                        class="rounded-full px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                        :class="badge.class"
                    >
                        {{ badge.label }}
                    </span>
                </div>
                <p class="mt-0.5 flex flex-wrap items-center gap-x-2 truncate text-[12px] font-medium text-ink/45">
                    <span v-if="person.trade" class="truncate">{{ person.trade }}</span>
                    <span v-if="person.trade && metaTail" class="text-ink/20">·</span>
                    <span v-if="metaTail" class="truncate">{{ metaTail }}</span>
                </p>
            </div>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </article>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    person: { type: Object, required: true },
    badges: { type: Array, default: () => [] },
    metaTail: { type: String, default: '' },
});

const initials = computed(() => {
    const source = props.person.person || props.person.name || '';
    const parts = String(source).trim().split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return (parts[0]?.[0] || 'I').toUpperCase();
});
</script>
