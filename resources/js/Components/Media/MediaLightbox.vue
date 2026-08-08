<template>
    <Teleport to="body">
        <Transition name="media-lb">
            <div
                v-if="open"
                class="media-lightbox fixed inset-0 z-[100] flex flex-col"
                role="dialog"
                aria-modal="true"
                :aria-label="ariaLabel"
                @keydown="onKeydown"
            >
                <!-- Backdrop -->
                <button
                    type="button"
                    class="absolute inset-0 bg-[#070f1c]/88 backdrop-blur-xl"
                    aria-label="Close gallery"
                    @click="close"
                />

                <!-- Top bar -->
                <div
                    class="relative z-10 flex shrink-0 items-center justify-between gap-3 px-4 py-3 sm:px-6"
                    style="padding-top: max(0.75rem, env(safe-area-inset-top))"
                >
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-white/95">
                            {{ currentCaption }}
                        </p>
                        <p v-if="items.length > 1" class="mt-0.5 text-xs font-medium text-white/45">
                            {{ index + 1 }} of {{ items.length }}
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-1.5">
                        <a
                            v-if="current?.url"
                            :href="current.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="tap-target flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white/80 ring-1 ring-white/10 transition hover:bg-white/15 hover:text-white"
                            aria-label="Open original"
                            title="Open original"
                        >
                            <i class="ti ti-external-link text-lg" aria-hidden="true" />
                        </a>
                        <button
                            type="button"
                            class="tap-target flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white/80 ring-1 ring-white/10 transition hover:bg-white/15 hover:text-white"
                            aria-label="Close"
                            @click="close"
                        >
                            <i class="ti ti-x text-xl" aria-hidden="true" />
                        </button>
                    </div>
                </div>

                <!-- Stage -->
                <div
                    class="relative z-10 flex min-h-0 flex-1 items-center justify-center px-2 sm:px-14"
                    @touchstart.passive="onTouchStart"
                    @touchend.passive="onTouchEnd"
                >
                    <button
                        v-if="items.length > 1"
                        type="button"
                        class="tap-target absolute left-2 z-20 hidden h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/15 backdrop-blur-md transition hover:bg-white/20 sm:flex"
                        aria-label="Previous"
                        @click="prev"
                    >
                        <i class="ti ti-chevron-left text-xl" aria-hidden="true" />
                    </button>

                    <button
                        v-if="items.length > 1"
                        type="button"
                        class="tap-target absolute right-2 z-20 hidden h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/15 backdrop-blur-md transition hover:bg-white/20 sm:flex"
                        aria-label="Next"
                        @click="next"
                    >
                        <i class="ti ti-chevron-right text-xl" aria-hidden="true" />
                    </button>

                    <Transition :name="slideName" mode="out-in">
                        <div
                            :key="currentKey"
                            class="flex max-h-full w-full max-w-5xl items-center justify-center"
                        >
                            <img
                                v-if="current?.kind === 'image'"
                                :src="current.url"
                                :alt="currentCaption"
                                class="media-stage-asset max-h-[min(72vh,780px)] w-auto max-w-full rounded-lg object-contain shadow-[0_24px_80px_-20px_rgba(0,0,0,0.55)] ring-1 ring-white/10 sm:rounded-xl"
                                draggable="false"
                            />

                            <div
                                v-else-if="current"
                                class="relative w-full max-w-4xl overflow-hidden rounded-lg bg-black/40 shadow-[0_24px_80px_-20px_rgba(0,0,0,0.55)] ring-1 ring-white/10 sm:rounded-xl"
                            >
                                <video
                                    ref="videoRef"
                                    :src="current.url"
                                    class="max-h-[min(72vh,780px)] w-full bg-black object-contain"
                                    controls
                                    playsinline
                                    preload="metadata"
                                    @click.stop
                                />
                            </div>
                        </div>
                    </Transition>
                </div>

                <!-- Mobile prev/next + filmstrip -->
                <div
                    class="relative z-10 shrink-0 px-4 pb-3 pt-2 sm:px-6"
                    style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))"
                >
                    <div
                        v-if="items.length > 1"
                        class="mb-3 flex items-center justify-center gap-3 sm:hidden"
                    >
                        <button
                            type="button"
                            class="tap-target flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/15"
                            aria-label="Previous"
                            @click="prev"
                        >
                            <i class="ti ti-chevron-left text-lg" aria-hidden="true" />
                        </button>
                        <button
                            type="button"
                            class="tap-target flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/15"
                            aria-label="Next"
                            @click="next"
                        >
                            <i class="ti ti-chevron-right text-lg" aria-hidden="true" />
                        </button>
                    </div>

                    <ul
                        v-if="items.length > 1"
                        class="mx-auto flex max-w-3xl gap-2 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                    >
                        <li
                            v-for="(item, i) in items"
                            :key="itemKey(item, i)"
                            class="shrink-0"
                        >
                            <button
                                type="button"
                                class="relative h-14 w-14 overflow-hidden rounded-lg ring-2 transition sm:h-16 sm:w-16"
                                :class="i === index
                                    ? 'ring-white scale-[1.03]'
                                    : 'ring-white/15 opacity-70 hover:opacity-100'"
                                :aria-label="`View item ${i + 1}`"
                                :aria-current="i === index ? 'true' : undefined"
                                @click="goTo(i)"
                            >
                                <img
                                    v-if="item.kind === 'image'"
                                    :src="item.url"
                                    alt=""
                                    class="h-full w-full object-cover"
                                />
                                <span
                                    v-else
                                    class="flex h-full w-full items-center justify-center bg-ink/80 text-white/80"
                                >
                                    <i class="ti ti-player-play-filled text-lg" aria-hidden="true" />
                                </span>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    items: { type: Array, default: () => [] },
    startIndex: { type: Number, default: 0 },
});

const emit = defineEmits(['close', 'update:show']);

const open = computed({
    get: () => props.show,
    set: (v) => emit('update:show', v),
});

const index = ref(0);
const videoRef = ref(null);
const slideName = ref('media-slide-next');
const touchStartX = ref(null);

const current = computed(() => props.items[index.value] || null);
const currentKey = computed(() => itemKey(current.value, index.value));
const currentCaption = computed(() => {
    const item = current.value;
    if (!item) {
        return 'Media';
    }
    if (item.original_name) {
        return item.original_name;
    }
    return item.kind === 'video' ? 'Video' : 'Photo';
});

const ariaLabel = computed(() => `Media gallery — ${currentCaption.value}`);

const itemKey = (item, i) => item?.id ?? item?.url ?? i;

const pauseVideo = () => {
    const el = videoRef.value;
    if (el) {
        el.pause();
    }
};

const playVideoIfNeeded = async () => {
    await nextTick();
    const el = videoRef.value;
    if (!el || current.value?.kind !== 'video') {
        return;
    }
    try {
        el.currentTime = 0;
        await el.play();
    } catch {
        // Autoplay may be blocked — controls remain available.
    }
};

const goTo = (i) => {
    if (!props.items.length) {
        return;
    }
    const len = props.items.length;
    const nextIndex = ((i % len) + len) % len;
    if (nextIndex === index.value) {
        return;
    }

    const wrappingForward = index.value === len - 1 && nextIndex === 0;
    const wrappingBack = index.value === 0 && nextIndex === len - 1;
    slideName.value = wrappingBack || (!wrappingForward && nextIndex < index.value)
        ? 'media-slide-prev'
        : 'media-slide-next';

    pauseVideo();
    index.value = nextIndex;
    playVideoIfNeeded();
};

const next = () => goTo(index.value + 1);
const prev = () => goTo(index.value - 1);

const close = () => {
    pauseVideo();
    emit('close');
    emit('update:show', false);
};

const onKeydown = (e) => {
    if (!props.show) {
        return;
    }
    if (e.key === 'Escape') {
        e.preventDefault();
        close();
    } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        next();
    } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        prev();
    }
};

const onTouchStart = (e) => {
    touchStartX.value = e.changedTouches?.[0]?.clientX ?? null;
};

const onTouchEnd = (e) => {
    if (touchStartX.value == null || props.items.length < 2) {
        return;
    }
    const endX = e.changedTouches?.[0]?.clientX ?? touchStartX.value;
    const delta = endX - touchStartX.value;
    touchStartX.value = null;
    if (Math.abs(delta) < 48) {
        return;
    }
    if (delta < 0) {
        next();
    } else {
        prev();
    }
};

watch(
    () => props.show,
    (visible) => {
        if (visible) {
            index.value = Math.min(
                Math.max(0, props.startIndex),
                Math.max(0, props.items.length - 1),
            );
            document.body.style.overflow = 'hidden';
            window.addEventListener('keydown', onKeydown);
            playVideoIfNeeded();
        } else {
            document.body.style.overflow = '';
            window.removeEventListener('keydown', onKeydown);
            pauseVideo();
        }
    },
);

watch(
    () => props.startIndex,
    (v) => {
        if (props.show) {
            index.value = Math.min(Math.max(0, v), Math.max(0, props.items.length - 1));
        }
    },
);

onUnmounted(() => {
    document.body.style.overflow = '';
    window.removeEventListener('keydown', onKeydown);
});
</script>

<style scoped>
.media-lb-enter-active,
.media-lb-leave-active {
    transition: opacity 0.28s cubic-bezier(0.22, 1, 0.36, 1);
}

.media-lb-enter-active .media-stage-asset,
.media-lb-enter-active .relative.w-full {
    transition: transform 0.36s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.36s ease;
}

.media-lb-enter-from,
.media-lb-leave-to {
    opacity: 0;
}

.media-lb-enter-from .media-stage-asset,
.media-lb-enter-from .relative.w-full {
    opacity: 0;
    transform: scale(0.96) translateY(8px);
}

.media-slide-next-enter-active,
.media-slide-next-leave-active,
.media-slide-prev-enter-active,
.media-slide-prev-leave-active {
    transition: opacity 0.22s ease, transform 0.22s cubic-bezier(0.22, 1, 0.36, 1);
}

.media-slide-next-enter-from {
    opacity: 0;
    transform: translateX(18px);
}

.media-slide-next-leave-to {
    opacity: 0;
    transform: translateX(-14px);
}

.media-slide-prev-enter-from {
    opacity: 0;
    transform: translateX(-18px);
}

.media-slide-prev-leave-to {
    opacity: 0;
    transform: translateX(14px);
}
</style>
