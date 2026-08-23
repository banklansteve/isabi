<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="visible"
                class="pointer-events-none fixed inset-x-0 top-0 z-[100]"
                role="progressbar"
                aria-valuemin="0"
                aria-valuemax="100"
                :aria-valuenow="Math.round(progress)"
                aria-label="Page loading"
            >
                <div
                    class="h-[3px] origin-left rounded-b-full shadow-[0_0_12px_rgba(47,111,237,0.45)] transition-[width] duration-200 ease-out"
                    style="background-image: linear-gradient(90deg, #2f6fed, #ff6a3d)"
                    :style="{ width: `${progress}%` }"
                />
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

const visible = ref(false);
const progress = ref(0);
let tickTimer = null;
let removeStart = null;
let removeProgress = null;
let removeFinish = null;
let removeError = null;

const clearTimers = () => {
    if (tickTimer) {
        clearInterval(tickTimer);
        tickTimer = null;
    }
};

const finish = () => {
    clearTimers();
    progress.value = 100;
    window.setTimeout(() => {
        visible.value = false;
        progress.value = 0;
    }, 180);
};

const shouldShow = (visit) => {
    if (!visit || visit.prefetch || visit.showProgress === false) {
        return false;
    }

    const url = String(visit.url || '');
    return !url.includes('/admin');
};

onMounted(() => {
    removeStart = router.on('start', (event) => {
        if (!shouldShow(event.detail?.visit)) {
            return;
        }

        clearTimers();
        progress.value = 14;
        visible.value = true;
        tickTimer = window.setInterval(() => {
            if (progress.value < 86) {
                progress.value += Math.max(1, (90 - progress.value) * 0.08);
            }
        }, 180);
    });

    removeProgress = router.on('progress', (event) => {
        if (!visible.value || event.detail?.progress?.percentage == null) {
            return;
        }

        progress.value = Math.max(progress.value, event.detail.progress.percentage);
    });

    removeFinish = router.on('finish', finish);
    removeError = router.on('error', finish);
});

onUnmounted(() => {
    clearTimers();
    removeStart?.();
    removeProgress?.();
    removeFinish?.();
    removeError?.();
});
</script>
