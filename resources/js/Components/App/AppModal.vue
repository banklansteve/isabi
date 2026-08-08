<template>
    <Teleport to="body">
        <Transition name="app-modal">
            <div
                v-if="show"
                class="fixed inset-0 z-[95] flex"
                :class="sheet ? 'items-end justify-center sm:items-center sm:p-6' : 'items-center justify-center p-4 sm:p-6'"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="titleId"
            >
                <button
                    type="button"
                    class="app-modal__backdrop absolute inset-0 bg-[#070f1c]/55 backdrop-blur-sm"
                    aria-label="Close dialog"
                    :disabled="!closeable"
                    @click="onBackdrop"
                />

                <div
                    ref="panelRef"
                    class="app-modal__panel relative z-10 flex w-full flex-col overflow-hidden bg-white shadow-premium-hover ring-1 ring-ink/[0.08]"
                    :class="[
                        sheet
                            ? 'rounded-t-3xl sm:rounded-3xl'
                            : 'rounded-3xl',
                        sizeClass,
                    ]"
                    :style="sheet ? { paddingBottom: 'max(0px, env(safe-area-inset-bottom))' } : undefined"
                    tabindex="-1"
                    @keydown.esc.prevent="onEscape"
                >
                    <div
                        v-if="sheet"
                        class="mx-auto mt-3 h-1 w-10 shrink-0 rounded-full bg-ink/10 sm:hidden"
                        aria-hidden="true"
                    />

                    <button
                        v-if="closeable && !showHeader"
                        type="button"
                        class="tap-target absolute right-3 top-3 z-20 flex h-9 w-9 items-center justify-center rounded-xl text-ink/35 transition hover:bg-pale hover:text-ink"
                        aria-label="Close"
                        @click="close"
                    >
                        <i class="ti ti-x text-lg" aria-hidden="true" />
                    </button>

                    <header
                        v-if="showHeader"
                        class="flex items-start gap-3 px-5 pb-0 pt-4 sm:px-6 sm:pt-6"
                    >
                        <div
                            v-if="icon || $slots.icon"
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-xl"
                            :class="iconToneClass"
                        >
                            <slot name="icon">
                                <i :class="icon" aria-hidden="true" />
                            </slot>
                        </div>

                        <div class="min-w-0 flex-1 pt-0.5">
                            <h2
                                v-if="title || $slots.title"
                                :id="titleId"
                                class="text-lg font-bold tracking-tight text-ink"
                            >
                                <slot name="title">{{ title }}</slot>
                            </h2>
                            <p
                                v-if="description || $slots.description"
                                class="mt-1 text-sm font-medium leading-relaxed text-ink/55"
                            >
                                <slot name="description">{{ description }}</slot>
                            </p>
                        </div>

                        <button
                            v-if="closeable"
                            type="button"
                            class="tap-target -me-1 -mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-ink/35 transition hover:bg-pale hover:text-ink"
                            aria-label="Close"
                            @click="close"
                        >
                            <i class="ti ti-x text-lg" aria-hidden="true" />
                        </button>
                    </header>

                    <div
                        class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6"
                        :class="showHeader ? 'pt-5' : 'pt-6 sm:pt-7'"
                    >
                        <slot />
                    </div>

                    <footer
                        v-if="$slots.footer"
                        class="flex flex-col-reverse gap-2 border-t border-ink/[0.06] px-5 py-4 sm:flex-row sm:justify-end sm:gap-3 sm:px-6"
                    >
                        <slot name="footer" />
                    </footer>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, nextTick, onUnmounted, ref, useId, useSlots, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    icon: { type: String, default: '' },
    /** visual tone for the icon chip */
    iconTone: {
        type: String,
        default: 'base',
        validator: (v) => ['base', 'whatsapp', 'coral', 'amber', 'neutral'].includes(v),
    },
    /** sm | md | lg | xl */
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg', 'xl'].includes(v),
    },
    /** Bottom sheet on mobile, centered card on desktop */
    sheet: { type: Boolean, default: false },
    closeable: { type: Boolean, default: true },
    closeOnBackdrop: { type: Boolean, default: true },
});

const emit = defineEmits(['close', 'opened']);

const slots = useSlots();
const panelRef = ref(null);
const titleId = useId();

const showHeader = computed(
    () =>
        Boolean(props.title)
        || Boolean(props.description)
        || Boolean(props.icon)
        || Boolean(slots.title)
        || Boolean(slots.description)
        || Boolean(slots.icon),
);

const sizeClass = computed(() => {
    const map = {
        sm: 'max-w-sm',
        md: 'max-w-md',
        lg: 'max-w-lg',
        xl: 'max-w-xl',
    };
    return map[props.size] || map.md;
});

const iconToneClass = computed(() => {
    const map = {
        base: 'bg-tint text-base-action',
        whatsapp: 'bg-[#25D366]/15 text-[#128C7E]',
        coral: 'bg-coral-tint text-coral-deep',
        amber: 'bg-amber-50 text-amber-700',
        neutral: 'bg-pale text-ink/55',
    };
    return map[props.iconTone] || map.base;
});

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const onBackdrop = () => {
    if (props.closeable && props.closeOnBackdrop) {
        close();
    }
};

const onEscape = () => {
    if (props.show) {
        close();
    }
};

const onGlobalKeydown = (e) => {
    if (e.key === 'Escape' && props.show) {
        e.preventDefault();
        close();
    }
};

watch(
    () => props.show,
    async (open) => {
        if (open) {
            document.body.style.overflow = 'hidden';
            window.addEventListener('keydown', onGlobalKeydown);
            await nextTick();
            panelRef.value?.focus?.({ preventScroll: true });
            emit('opened');
        } else {
            document.body.style.overflow = '';
            window.removeEventListener('keydown', onGlobalKeydown);
        }
    },
);

onUnmounted(() => {
    document.body.style.overflow = '';
    window.removeEventListener('keydown', onGlobalKeydown);
});
</script>

<style scoped>
.app-modal-enter-active,
.app-modal-leave-active {
    transition: opacity 0.26s cubic-bezier(0.22, 1, 0.36, 1);
}

.app-modal-enter-active .app-modal__panel,
.app-modal-leave-active .app-modal__panel {
    transition:
        transform 0.3s cubic-bezier(0.22, 1, 0.36, 1),
        opacity 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

.app-modal-enter-from,
.app-modal-leave-to {
    opacity: 0;
}

.app-modal-enter-from .app-modal__panel,
.app-modal-leave-to .app-modal__panel {
    opacity: 0;
    transform: translateY(16px) scale(0.98);
}

@media (max-width: 639px) {
    .app-modal-enter-from .app-modal__panel,
    .app-modal-leave-to .app-modal__panel {
        transform: translateY(24px);
    }
}
</style>
