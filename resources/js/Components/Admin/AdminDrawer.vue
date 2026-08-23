<template>
    <Teleport to="body">
        <button
            type="button"
            class="fixed inset-0 z-[60] bg-ink/40 backdrop-blur-[3px] transition-opacity duration-[420ms] ease-[cubic-bezier(0.32,0.72,0,1)]"
            :class="open ? 'opacity-100' : 'pointer-events-none opacity-0'"
            :tabindex="open ? 0 : -1"
            aria-label="Close panel"
            @click="$emit('close')"
        />

        <aside
            class="fixed inset-y-0 right-0 z-[70] flex w-full flex-col bg-white shadow-[-24px_0_80px_-28px_rgba(15,23,42,0.28)] will-change-transform transition-transform duration-[480ms] ease-[cubic-bezier(0.32,0.72,0,1)] sm:rounded-l-[1.75rem]"
            :class="[
                size === 'lg' ? 'max-w-[44rem]' : 'max-w-[32rem]',
                open ? 'translate-x-0' : 'pointer-events-none translate-x-full',
            ]"
            role="dialog"
            aria-modal="true"
            :aria-hidden="!open"
        >
            <header class="shrink-0 border-b border-ink/[0.06] px-5 py-4">
                <slot name="header">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p v-if="eyebrow" class="text-[11px] font-bold uppercase tracking-[0.14em] text-base-action">
                                {{ eyebrow }}
                            </p>
                            <h2 class="mt-1 truncate text-[17px] font-bold tracking-tight text-ink">{{ title }}</h2>
                        </div>
                        <button
                            type="button"
                            class="tap-target flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-ink/40 transition-colors duration-150 hover:bg-pale hover:text-ink"
                            aria-label="Close"
                            @click="$emit('close')"
                        >
                            <i class="ti ti-x text-lg" aria-hidden="true" />
                        </button>
                    </div>
                </slot>
            </header>
            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 py-5">
                <slot />
            </div>
            <footer v-if="$slots.footer" class="shrink-0 border-t border-ink/[0.06] bg-white/95 px-5 py-3 backdrop-blur-sm">
                <slot name="footer" />
            </footer>
        </aside>
    </Teleport>
</template>

<script setup>
import { onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
    eyebrow: { type: String, default: '' },
    size: { type: String, default: 'md' },
});

const emit = defineEmits(['close']);

const onKey = (event) => {
    if (event.key === 'Escape' && props.open) {
        emit('close');
    }
};

const lockScroll = (locked) => {
    document.body.style.overflow = locked ? 'hidden' : '';
};

watch(
    () => props.open,
    (open) => lockScroll(open),
    { immediate: true },
);

onMounted(() => window.addEventListener('keydown', onKey));
onUnmounted(() => {
    window.removeEventListener('keydown', onKey);
    lockScroll(false);
});
</script>
