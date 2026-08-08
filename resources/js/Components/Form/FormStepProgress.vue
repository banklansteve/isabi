<template>
    <nav aria-label="Form progress">
        <ol class="grid grid-cols-3 gap-2">
            <li
                v-for="(item, index) in steps"
                :key="item.key"
                class="min-w-0"
            >
                <button
                    type="button"
                    class="group flex w-full flex-col gap-1.5 rounded-xl px-1 py-1 text-left transition-colors"
                    :disabled="!canJumpTo(index)"
                    :class="canJumpTo(index) ? 'cursor-pointer' : 'cursor-default opacity-80'"
                    @click="onGo(index)"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold transition-colors"
                            :class="chipClass(index)"
                        >
                            <i
                                v-if="index < model"
                                class="ti ti-check text-sm"
                                aria-hidden="true"
                            />
                            <template v-else>{{ index + 1 }}</template>
                        </span>
                        <span
                            class="truncate text-[11px] font-semibold uppercase tracking-[0.08em]"
                            :class="index === model ? 'text-ink' : 'text-ink/40'"
                        >
                            {{ item.label }}
                        </span>
                    </div>
                    <div class="h-1 overflow-hidden rounded-full bg-ink/[0.06]">
                        <div
                            class="h-full rounded-full transition-all duration-300"
                            :class="barClass(index)"
                            :style="{ width: barWidth(index) }"
                        />
                    </div>
                    <p
                        v-if="item.hint"
                        class="truncate text-[10px] font-medium"
                        :class="index === model ? 'text-ink/45' : 'text-ink/30'"
                    >
                        {{ item.hint }}
                    </p>
                </button>
            </li>
        </ol>
    </nav>
</template>

<script setup>
const model = defineModel({ type: Number, default: 0 });

const props = defineProps({
    steps: {
        type: Array,
        required: true,
    },
    /** Highest step the user has reached (inclusive). */
    maxReachable: { type: Number, default: 0 },
});

const emit = defineEmits(['go']);

const canJumpTo = (index) => index <= props.maxReachable;

const onGo = (index) => {
    if (!canJumpTo(index)) {
        return;
    }
    model.value = index;
    emit('go', index);
};

const chipClass = (index) => {
    if (index < model.value) {
        return 'bg-base-action text-white';
    }
    if (index === model.value) {
        return 'bg-base-action text-white ring-4 ring-base-action/15';
    }
    return 'bg-ink/[0.08] text-ink/40';
};

const barClass = (index) => {
    if (index < model.value) {
        return 'bg-base-action';
    }
    if (index === model.value) {
        return 'bg-base-action/70';
    }
    return 'bg-transparent';
};

const barWidth = (index) => {
    if (index < model.value) {
        return '100%';
    }
    if (index === model.value) {
        return '55%';
    }
    return '0%';
};
</script>
