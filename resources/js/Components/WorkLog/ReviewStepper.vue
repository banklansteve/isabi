<template>
    <ol class="grid grid-cols-3 gap-2">
        <li
            v-for="(step, index) in steps"
            :key="step.key"
            class="relative min-w-0 text-center"
        >
            <span
                v-if="index < steps.length - 1"
                class="absolute left-1/2 top-[1.125rem] h-[3px] w-[calc(100%+0.5rem)] -translate-y-1/2 rounded-full bg-ink/[0.07]"
                aria-hidden="true"
            />
            <span
                v-if="index < steps.length - 1 && step.state === 'done'"
                class="absolute left-1/2 top-[1.125rem] h-[3px] w-[calc(100%+0.5rem)] -translate-y-1/2 rounded-full bg-gradient-to-r from-base to-base-action"
                :class="steps[index + 1].state === 'done' ? '' : 'opacity-35'"
                aria-hidden="true"
            />

            <span
                class="relative z-[1] mx-auto flex h-9 w-9 items-center justify-center rounded-full text-sm transition-colors duration-300"
                :class="dotClass(step.state)"
            >
                <i
                    :class="step.state === 'done' ? 'ti ti-check' : step.icon"
                    aria-hidden="true"
                />
            </span>

            <p
                class="mt-2.5 truncate text-[11px] font-bold tracking-tight sm:text-xs"
                :class="step.state === 'pending' ? 'text-ink/35' : 'text-ink'"
            >
                {{ step.label }}
            </p>
            <p class="mt-0.5 truncate text-[10px] font-medium text-ink/40 sm:text-[11px]">
                {{ step.sub }}
            </p>
        </li>
    </ol>
</template>

<script setup>
defineProps({
    /** @type {{ key: string, label: string, sub: string, icon: string, state: 'done'|'current'|'pending' }[]} */
    steps: { type: Array, default: () => [] },
});

const dotClass = (state) => {
    if (state === 'done') {
        return 'bg-base-action text-white shadow-[0_6px_16px_-6px_rgba(26,79,181,0.6)] ring-4 ring-tint';
    }
    if (state === 'current') {
        return 'bg-white text-base-action ring-[3px] ring-base-action/30';
    }
    return 'bg-pale text-ink/25 ring-1 ring-ink/[0.07]';
};
</script>
