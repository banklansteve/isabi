<template>
    <Teleport to="body">
        <div
            class="pointer-events-none fixed inset-x-0 bottom-0 z-[90] flex flex-col-reverse items-center gap-3 px-4 pb-[max(5.5rem,calc(env(safe-area-inset-bottom)+4.75rem))] sm:items-end sm:px-6 md:pb-[max(1.25rem,env(safe-area-inset-bottom))]"
            aria-live="polite"
            aria-relevant="additions"
        >
            <TransitionGroup name="toast">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="toast-card pointer-events-auto relative w-full max-w-[24rem] overflow-hidden rounded-[1.35rem] text-white shadow-[0_24px_60px_-20px_rgba(7,20,39,0.55)]"
                    :class="surfaceClass(toast.type)"
                    role="status"
                >
                    <!-- Atmosphere -->
                    <div
                        class="pointer-events-none absolute inset-0 opacity-90"
                        :class="glowClass(toast.type)"
                        aria-hidden="true"
                    />
                    <div
                        class="pointer-events-none absolute inset-0 opacity-[0.14]"
                        style="
                            background-image: radial-gradient(
                                rgba(255, 255, 255, 0.22) 0.6px,
                                transparent 0.6px
                            );
                            background-size: 14px 14px;
                        "
                        aria-hidden="true"
                    />
                    <div
                        class="pointer-events-none absolute -right-8 -top-10 h-28 w-28 rounded-full blur-2xl"
                        :class="orbClass(toast.type)"
                        aria-hidden="true"
                    />

                    <div class="relative flex items-start gap-3.5 px-4 pb-3.5 pt-4 sm:px-5">
                        <span
                            class="toast-icon relative mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-[1.25rem] ring-1 ring-white/20"
                            :class="iconWrapClass(toast.type)"
                        >
                            <i :class="iconClass(toast.type)" aria-hidden="true" />
                        </span>

                        <div class="min-w-0 flex-1 pt-0.5">
                            <p
                                v-if="toast.title"
                                class="font-editorial text-[0.95rem] font-semibold leading-tight tracking-tight text-white"
                            >
                                {{ toast.title }}
                            </p>
                            <p
                                class="text-sm font-semibold leading-snug tracking-tight"
                                :class="toast.title ? 'mt-1 text-white/72' : 'pt-1.5 text-white'"
                            >
                                {{ toast.message }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="tap-target -me-1 -mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-white/45 transition-colors hover:bg-white/10 hover:text-white"
                            aria-label="Dismiss"
                            @click="dismiss(toast.id)"
                        >
                            <i class="ti ti-x text-base" aria-hidden="true" />
                        </button>
                    </div>

                    <div class="relative mx-4 mb-3.5 h-[2px] overflow-hidden rounded-full bg-white/10 sm:mx-5">
                        <div
                            class="toast-progress h-full rounded-full"
                            :class="progressClass(toast.type)"
                            :style="{ animationDuration: `${toast.duration}ms` }"
                        />
                    </div>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, watch } from 'vue';

const page = usePage();
const toasts = ref([]);
let seq = 0;
const seenKeys = new Set();

const normalizeType = (type) => {
    if (type === 'error' || type === 'danger') return 'error';
    if (type === 'warning' || type === 'warn') return 'warning';
    if (type === 'info') return 'info';
    return 'success';
};

const push = (toast) => {
    if (!toast?.message) {
        return;
    }

    const id = ++seq;
    const duration = Number(toast.duration) > 0 ? Number(toast.duration) : 4800;
    const type = normalizeType(toast.type);

    toasts.value = [
        ...toasts.value,
        {
            id,
            type,
            title: toast.title ? String(toast.title) : '',
            message: String(toast.message),
            duration,
        },
    ].slice(-4);

    window.setTimeout(() => dismiss(id), duration);
};

const dismiss = (id) => {
    toasts.value = toasts.value.filter((t) => t.id !== id);
};

const flashKey = (toast) =>
    `${toast?.type || 'success'}:${toast?.title || ''}:${toast?.message || ''}:${toast?.duration || ''}`;

const consumeFlash = (toast) => {
    if (!toast || typeof toast !== 'object' || !toast.message) {
        return;
    }
    const key = flashKey(toast);
    if (seenKeys.has(key)) {
        return;
    }
    seenKeys.add(key);
    window.setTimeout(() => seenKeys.delete(key), 2000);
    push(toast);
};

const onCustomToast = (event) => push(event.detail || {});

const surfaceClass = (type) => {
    if (type === 'error') {
        return 'bg-gradient-to-br from-[#3a1520] via-[#1c0f18] to-[#0b1424] ring-1 ring-coral/35';
    }
    if (type === 'warning') {
        return 'bg-gradient-to-br from-[#3a2a12] via-[#1c160e] to-[#0b1424] ring-1 ring-amber-400/30';
    }
    if (type === 'info') {
        return 'bg-gradient-to-br from-[#123B72] via-[#0d274d] to-[#071427] ring-1 ring-white/15';
    }
    return 'bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] ring-1 ring-white/15';
};

const glowClass = (type) => {
    if (type === 'error') {
        return 'bg-[radial-gradient(80%_90%_at_0%_0%,rgba(255,106,61,0.28),transparent_55%)]';
    }
    if (type === 'warning') {
        return 'bg-[radial-gradient(80%_90%_at_0%_0%,rgba(251,191,36,0.22),transparent_55%)]';
    }
    if (type === 'info') {
        return 'bg-[radial-gradient(80%_90%_at_0%_0%,rgba(147,197,253,0.2),transparent_55%)]';
    }
    return 'bg-[radial-gradient(80%_90%_at_0%_0%,rgba(255,255,255,0.16),transparent_55%),radial-gradient(60%_70%_at_100%_100%,rgba(255,106,61,0.18),transparent_50%)]';
};

const orbClass = (type) => {
    if (type === 'error') return 'bg-coral/30';
    if (type === 'warning') return 'bg-amber-400/25';
    if (type === 'info') return 'bg-sky-300/20';
    return 'bg-coral/25';
};

const iconWrapClass = (type) => {
    if (type === 'error') return 'bg-coral/20 text-coral';
    if (type === 'warning') return 'bg-amber-400/15 text-amber-200';
    if (type === 'info') return 'bg-white/10 text-sky-100';
    return 'bg-white/12 text-white';
};

const iconClass = (type) => {
    if (type === 'error') return 'ti ti-alert-triangle';
    if (type === 'warning') return 'ti ti-alert-circle';
    if (type === 'info') return 'ti ti-info-circle';
    return 'ti ti-rosette-discount-check';
};

const progressClass = (type) => {
    if (type === 'error') return 'bg-gradient-to-r from-coral via-[#ff8f66] to-coral';
    if (type === 'warning') return 'bg-gradient-to-r from-amber-300 via-amber-200 to-amber-300';
    if (type === 'info') return 'bg-gradient-to-r from-sky-200 via-white to-sky-200';
    return 'bg-gradient-to-r from-white via-coral to-white';
};

watch(
    () => page.props.flash?.toast,
    (toast) => consumeFlash(toast),
    { immediate: true, deep: true },
);

let removeInertiaListener = null;

onMounted(() => {
    window.addEventListener('isabi:toast', onCustomToast);
    removeInertiaListener = router.on('success', (event) => {
        consumeFlash(event.detail?.page?.props?.flash?.toast);
    });
});

onUnmounted(() => {
    window.removeEventListener('isabi:toast', onCustomToast);
    removeInertiaListener?.();
});
</script>

<style scoped>
.toast-card {
    animation: toast-sheen 4.5s ease-in-out infinite;
}

.toast-icon {
    animation: toast-icon-in 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.toast-enter-active {
    transition:
        opacity 0.45s cubic-bezier(0.22, 1, 0.36, 1),
        transform 0.45s cubic-bezier(0.22, 1, 0.36, 1),
        filter 0.45s cubic-bezier(0.22, 1, 0.36, 1);
}

.toast-leave-active {
    transition:
        opacity 0.28s ease,
        transform 0.28s ease,
        filter 0.28s ease;
}

.toast-enter-from {
    opacity: 0;
    transform: translateY(18px) scale(0.94);
    filter: blur(4px);
}

.toast-leave-to {
    opacity: 0;
    transform: translateY(10px) scale(0.96);
    filter: blur(2px);
}

.toast-move {
    transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
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

@keyframes toast-icon-in {
    from {
        opacity: 0;
        transform: scale(0.7) rotate(-8deg);
    }
    to {
        opacity: 1;
        transform: scale(1) rotate(0deg);
    }
}

@keyframes toast-sheen {
    0%,
    100% {
        box-shadow:
            0 24px 60px -20px rgba(7, 20, 39, 0.55),
            0 0 0 1px rgba(255, 255, 255, 0.06) inset;
    }
    50% {
        box-shadow:
            0 28px 70px -18px rgba(26, 79, 181, 0.45),
            0 0 0 1px rgba(255, 255, 255, 0.1) inset;
    }
}

@media (prefers-reduced-motion: reduce) {
    .toast-card,
    .toast-icon {
        animation: none;
    }

    .toast-enter-active,
    .toast-leave-active,
    .toast-move {
        transition-duration: 0.01ms;
    }
}
</style>
