<template>
    <Teleport to="body">
        <button
            type="button"
            class="admin-drawer-overlay fixed inset-0 bg-ink/40"
            :class="[
                stacked ? 'z-[72]' : 'z-[60]',
                open ? 'is-open' : 'pointer-events-none',
            ]"
            :tabindex="open ? 0 : -1"
            aria-label="Close panel"
            @click="emit('close')"
        />

        <aside
            class="admin-drawer-panel fixed inset-y-0 right-0 flex w-full flex-col bg-white sm:rounded-l-[1.75rem]"
            :class="[
                stacked ? 'z-[76]' : 'z-[70]',
                size === 'lg' ? 'max-w-[44rem]' : 'max-w-[32rem]',
                open ? 'is-open' : 'pointer-events-none',
                animating ? 'is-animating' : '',
            ]"
            role="dialog"
            aria-modal="true"
            :aria-hidden="!open"
            :inert="!open"
            @transitionend="onPanelTransitionEnd"
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
                            @click="emit('close')"
                        >
                            <i class="ti ti-x text-lg" aria-hidden="true" />
                        </button>
                    </div>
                </slot>
            </header>
            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 py-5">
                <slot />
            </div>
            <footer
                class="shrink-0 border-t border-ink/[0.06] bg-white px-5 py-3 empty:hidden [&:not(:has(*))]:hidden"
            >
                <slot name="footer" />
            </footer>
        </aside>
    </Teleport>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

let nextDrawerId = 0;
const drawerStack = [];
const PANEL_MS = 260;

const lockScroll = () => {
    document.body.style.overflow = drawerStack.length ? 'hidden' : '';
};

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
    eyebrow: { type: String, default: '' },
    size: { type: String, default: 'md' },
});

const emit = defineEmits(['close', 'entered', 'exited']);

const drawerId = nextDrawerId += 1;
const depth = ref(0);
const stacked = computed(() => depth.value > 0);
const animating = ref(false);
let settleTimer = 0;

const syncStack = (open) => {
    const index = drawerStack.indexOf(drawerId);
    if (open && index === -1) {
        drawerStack.push(drawerId);
    }
    if (!open && index !== -1) {
        drawerStack.splice(index, 1);
    }
    depth.value = Math.max(drawerStack.indexOf(drawerId), 0);
    lockScroll();
};

const settle = (open) => {
    if (!animating.value) {
        return;
    }
    animating.value = false;
    emit(open ? 'entered' : 'exited');
};

const onPanelTransitionEnd = (event) => {
    if (event.target !== event.currentTarget || event.propertyName !== 'transform') {
        return;
    }
    window.clearTimeout(settleTimer);
    settle(props.open);
};

const onKey = (event) => {
    if (event.key !== 'Escape' || !props.open) {
        return;
    }
    if (drawerStack[drawerStack.length - 1] === drawerId) {
        emit('close');
    }
};

watch(
    () => props.open,
    (open) => syncStack(open),
    { immediate: true },
);

watch(
    () => props.open,
    (open) => {
        animating.value = true;
        window.clearTimeout(settleTimer);
        settleTimer = window.setTimeout(() => {
            if (animating.value) {
                settle(open);
            }
        }, PANEL_MS + 40);
    },
);

onMounted(() => window.addEventListener('keydown', onKey));
onUnmounted(() => {
    window.removeEventListener('keydown', onKey);
    window.clearTimeout(settleTimer);
    syncStack(false);
});
</script>

<style>
.admin-drawer-overlay {
    opacity: 0;
    transition: opacity 240ms cubic-bezier(0.22, 1, 0.36, 1);
}

.admin-drawer-overlay.is-open {
    opacity: 1;
}

.admin-drawer-panel {
    transform: translate3d(100%, 0, 0);
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
    box-shadow: -8px 0 28px -18px rgba(15, 23, 42, 0.2);
    transition: transform 260ms cubic-bezier(0.22, 1, 0.36, 1);
}

.admin-drawer-panel.is-open {
    transform: translate3d(0, 0, 0);
}

.admin-drawer-panel.is-animating {
    will-change: transform;
}

@media (prefers-reduced-motion: reduce) {
    .admin-drawer-overlay,
    .admin-drawer-panel {
        transition-duration: 1ms;
    }
}
</style>
