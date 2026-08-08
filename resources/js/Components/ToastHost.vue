<template>
    <Teleport to="body">
        <div
            class="pointer-events-none fixed inset-x-0 top-0 z-[90] flex flex-col items-center gap-2.5 px-4 pt-[max(0.85rem,env(safe-area-inset-top))] sm:items-end sm:px-6 sm:pt-5"
            aria-live="polite"
        >
            <TransitionGroup name="toast">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto relative w-full max-w-[22rem] overflow-hidden rounded-2xl bg-white text-ink shadow-premium-hover ring-1 ring-ink/[0.08]"
                    role="status"
                >
                    <div class="flex items-start gap-3 px-4 py-3.5">
                        <span
                            class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-lg"
                            :class="
                                toast.type === 'error'
                                    ? 'bg-coral-tint text-coral-deep'
                                    : 'bg-tint text-base-action'
                            "
                        >
                            <i
                                :class="toast.type === 'error' ? 'ti ti-alert-circle' : 'ti ti-circle-check'"
                                aria-hidden="true"
                            />
                        </span>
                        <p class="min-w-0 flex-1 pt-1.5 text-sm font-semibold leading-snug tracking-tight text-ink">
                            {{ toast.message }}
                        </p>
                        <button
                            type="button"
                            class="tap-target -me-1 -mt-0.5 flex h-8 w-8 items-center justify-center rounded-lg text-ink/35 transition-colors hover:bg-pale hover:text-ink"
                            aria-label="Dismiss"
                            @click="dismiss(toast.id)"
                        >
                            <i class="ti ti-x text-base" aria-hidden="true" />
                        </button>
                    </div>
                    <div class="h-0.5 bg-pale">
                        <div
                            class="toast-progress h-full"
                            :class="toast.type === 'error' ? 'bg-coral' : 'bg-base-action'"
                            :style="{ animationDuration: `${toast.duration}ms` }"
                        />
                    </div>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, watch } from 'vue';

const page = usePage();
const toasts = ref([]);
let seq = 0;
const seenKeys = new Set();

const push = (toast) => {
    if (!toast?.message) {
        return;
    }

    const id = ++seq;
    const duration = Number(toast.duration) > 0 ? Number(toast.duration) : 5000;

    toasts.value = [
        ...toasts.value,
        {
            id,
            type: toast.type === 'error' ? 'error' : 'success',
            message: String(toast.message),
            duration,
        },
    ];

    window.setTimeout(() => dismiss(id), duration);
};

const dismiss = (id) => {
    toasts.value = toasts.value.filter((t) => t.id !== id);
};

const flashKey = (toast) =>
    `${toast?.type || 'success'}:${toast?.message || ''}:${toast?.duration || ''}`;

const onCustomToast = (event) => push(event.detail);

watch(
    () => page.props.flash?.toast,
    (toast) => {
        if (!toast || typeof toast !== 'object' || !toast.message) {
            return;
        }
        const key = flashKey(toast);
        if (seenKeys.has(key)) {
            return;
        }
        seenKeys.add(key);
        window.setTimeout(() => seenKeys.delete(key), 1500);
        push(toast);
    },
    { immediate: true, deep: true },
);

onMounted(() => {
    window.addEventListener('isabi:toast', onCustomToast);
});

onUnmounted(() => {
    window.removeEventListener('isabi:toast', onCustomToast);
});
</script>

<style scoped>
.toast-enter-active {
    transition:
        opacity 0.32s cubic-bezier(0.22, 1, 0.36, 1),
        transform 0.32s cubic-bezier(0.22, 1, 0.36, 1);
}

.toast-leave-active {
    transition:
        opacity 0.22s ease,
        transform 0.22s ease;
}

.toast-enter-from {
    opacity: 0;
    transform: translateY(-12px) scale(0.98);
}

.toast-leave-to {
    opacity: 0;
    transform: translateY(-8px) scale(0.97);
}

.toast-progress {
    width: 100%;
    transform-origin: left center;
    animation-name: toast-shrink;
    animation-timing-function: linear;
    animation-fill-mode: forwards;
}

@keyframes toast-shrink {
    from {
        transform: scaleX(1);
    }
    to {
        transform: scaleX(0);
    }
}
</style>
