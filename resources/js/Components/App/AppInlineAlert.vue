<template>
    <div
        class="flex gap-3 rounded-2xl px-4 py-3.5 ring-1"
        :class="toneClass"
        :role="tone === 'error' ? 'alert' : 'status'"
    >
        <span
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-lg"
            :class="iconWrapClass"
        >
            <i :class="resolvedIcon" aria-hidden="true" />
        </span>
        <div class="min-w-0 flex-1 pt-0.5">
            <p v-if="title" class="text-sm font-bold tracking-tight" :class="titleClass">
                {{ title }}
            </p>
            <p class="text-sm font-medium leading-relaxed" :class="[title ? 'mt-0.5' : '', bodyClass]">
                <slot>{{ message }}</slot>
            </p>
            <div v-if="$slots.action" class="mt-3">
                <slot name="action" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    tone: {
        type: String,
        default: 'error',
        validator: (v) => ['error', 'info', 'success', 'warning'].includes(v),
    },
    icon: { type: String, default: '' },
    title: { type: String, default: '' },
    message: { type: String, default: '' },
});

const resolvedIcon = computed(() => {
    if (props.icon) return props.icon;
    return {
        error: 'ti ti-alert-circle',
        info: 'ti ti-info-circle',
        success: 'ti ti-circle-check',
        warning: 'ti ti-alert-triangle',
    }[props.tone];
});

const toneClass = computed(
    () =>
        ({
            error: 'bg-coral/5 ring-coral/20',
            info: 'bg-tint/70 ring-base/15',
            success: 'bg-emerald-50 ring-emerald-200/70',
            warning: 'bg-amber-50 ring-amber-200/70',
        })[props.tone],
);

const iconWrapClass = computed(
    () =>
        ({
            error: 'bg-coral/10 text-coral',
            info: 'bg-white text-deep',
            success: 'bg-emerald-100 text-emerald-700',
            warning: 'bg-amber-100 text-amber-800',
        })[props.tone],
);

const titleClass = computed(
    () =>
        ({
            error: 'text-coral-deep',
            info: 'text-ink',
            success: 'text-emerald-900',
            warning: 'text-amber-950',
        })[props.tone],
);

const bodyClass = computed(
    () =>
        ({
            error: 'text-coral-deep/80',
            info: 'text-ink/55',
            success: 'text-emerald-900/70',
            warning: 'text-amber-900/70',
        })[props.tone],
);
</script>
