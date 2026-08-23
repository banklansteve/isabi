<template>
    <Teleport to="body">
        <Transition
            name="media-lb"
            @after-enter="onOpened"
            @after-leave="onClosed"
        >
            <div
                v-if="open"
                class="media-lightbox fixed inset-0 z-[100] flex flex-col overflow-hidden"
                role="dialog"
                aria-modal="true"
                :aria-label="ariaLabel"
                @keydown="onKeydown"
            >
                <!-- Backdrop -->
                <button
                    type="button"
                    class="absolute inset-0 bg-[#060e1c]"
                    aria-label="Close gallery"
                    @click="close"
                />

                <!-- Top bar -->
                <div
                    class="media-chrome relative z-10 flex shrink-0 items-center justify-between gap-3 px-4 py-3 sm:px-6"
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

                    <Transition :name="slideName" :css="canSlide">
                        <div
                            :key="currentKey"
                            class="media-stage relative flex max-h-full w-full max-w-5xl items-center justify-center"
                        >
                            <div
                                v-if="current?.kind === 'image'"
                                class="relative inline-flex max-h-[min(72vh,780px)] max-w-full items-center justify-center"
                            >
                                <img
                                    v-if="placeholderSrc"
                                    :src="placeholderSrc"
                                    alt=""
                                    class="media-stage-ph max-h-[min(72vh,780px)] w-auto max-w-full rounded-lg object-contain sm:rounded-xl"
                                    draggable="false"
                                />
                                <img
                                    :src="stageSrc"
                                    :alt="currentCaption"
                                    class="media-stage-asset max-h-[min(72vh,780px)] w-auto max-w-full rounded-lg object-contain shadow-[0_24px_80px_-20px_rgba(0,0,0,0.55)] ring-1 ring-white/10 sm:rounded-xl"
                                    :class="[
                                        stageReady ? 'is-ready' : '',
                                        placeholderSrc ? 'absolute inset-0 m-auto' : '',
                                    ]"
                                    decoding="async"
                                    fetchpriority="high"
                                    draggable="false"
                                    @load="onStageLoad"
                                />
                            </div>

                            <div
                                v-else-if="current"
                                class="relative w-full max-w-4xl overflow-hidden rounded-lg bg-black/40 shadow-[0_24px_80px_-20px_rgba(0,0,0,0.55)] ring-1 ring-white/10 sm:rounded-xl"
                            >
                                <video
                                    ref="videoRef"
                                    :src="current.url"
                                    :poster="current.poster_url || undefined"
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
                    class="media-chrome relative z-10 shrink-0 px-4 pb-3 pt-2 sm:px-6"
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
                        class="media-strip mx-auto flex max-w-3xl gap-2 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
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
                                    :src="item.thumb_url || item.url"
                                    alt=""
                                    loading="lazy"
                                    decoding="async"
                                    class="h-full w-full object-cover"
                                />
                                <span
                                    v-else
                                    class="flex h-full w-full items-center justify-center bg-ink/80 text-white/80"
                                >
                                    <svg class="h-4 w-4 translate-x-[1px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M8 5.14v13.72a1 1 0 0 0 1.53.85l10.8-6.86a1 1 0 0 0 0-1.7L9.53 4.29A1 1 0 0 0 8 5.14z" />
                                    </svg>
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
const stageReady = ref(true);
const canSlide = ref(false);

// Bumped on every open/navigate so a slow decode that finishes late can't
// reveal a stage the viewer has already moved on from.
let warmToken = 0;

const lockScroll = () => {
    // Compensate for the vanishing scrollbar so the page behind doesn't jump.
    const gap = window.innerWidth - document.documentElement.clientWidth;
    document.body.style.overflow = 'hidden';
    if (gap > 0) {
        document.body.style.paddingRight = `${gap}px`;
    }
};

const unlockScroll = () => {
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
};

const current = computed(() => props.items[index.value] || null);
const currentKey = computed(() => itemKey(current.value, index.value));
const stageSrc = computed(() => current.value?.preview_url || current.value?.url || '');
const placeholderSrc = computed(() => {
    const item = current.value;
    if (!item || item.kind !== 'image') {
        return '';
    }
    const thumb = item.thumb_url || '';
    const preview = item.preview_url || item.url || '';
    return thumb && thumb !== preview ? thumb : '';
});
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

/**
 * Prefetch the sharp preview. The thumbnail stays visible underneath so the
 * overlay can animate in with a real photo instead of an empty stage.
 */
const warmStage = (item) => {
    warmToken += 1;
    const token = warmToken;

    if (!item || item.kind !== 'image') {
        stageReady.value = true;
        return;
    }

    const src = item.preview_url || item.url;
    if (!src) {
        stageReady.value = true;
        return;
    }

    const img = new Image();
    img.decoding = 'async';
    img.src = src;

    if (img.complete && img.naturalWidth > 0) {
        stageReady.value = true;
        return;
    }

    const thumb = item.thumb_url || '';
    stageReady.value = !(thumb && thumb !== src);

    const reveal = () => {
        if (token === warmToken) {
            stageReady.value = true;
        }
    };

    if (typeof img.decode === 'function') {
        img.decode().then(reveal, reveal);
    } else {
        img.onload = reveal;
        img.onerror = reveal;
    }

    window.setTimeout(reveal, 2500);
};

const onStageLoad = () => {
    stageReady.value = true;
};

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
    warmStage(props.items[nextIndex]);
    playVideoIfNeeded();
    warmNeighbors();
};

const next = () => goTo(index.value + 1);
const prev = () => goTo(index.value - 1);

const onOpened = () => {
    canSlide.value = true;
    warmNeighbors();
};

const onClosed = () => {
    canSlide.value = false;
    unlockScroll();
};

const warmNeighbors = () => {
    if (props.items.length < 2) {
        return;
    }

    [index.value + 1, index.value - 1].forEach((raw) => {
        const len = props.items.length;
        const i = ((raw % len) + len) % len;
        const item = props.items[i];
        if (!item || item.kind !== 'image') {
            return;
        }
        const src = item.preview_url || item.url;
        if (!src) {
            return;
        }
        const img = new Image();
        img.decoding = 'async';
        img.src = src;
    });
};

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
            // Runs before Vue patches the overlay in, so the reflow from hiding
            // the page scrollbar lands ahead of the open animation rather than
            // stalling a frame in the middle of it.
            lockScroll();
            warmStage(props.items[index.value]);
            window.addEventListener('keydown', onKeydown);
            playVideoIfNeeded();
        } else {
            canSlide.value = false;
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
    unlockScroll();
    window.removeEventListener('keydown', onKeydown);
});
</script>

<style scoped>
/*
 * One motion in, one motion out: the overlay fades, the photo eases a
 * whisper of scale. No extra opacity on the image during open/close —
 * that was stacking fades and reading as a pop.
 */
.media-lb-enter-active,
.media-lb-leave-active {
    will-change: opacity;
}

.media-lb-enter-active {
    transition: opacity 0.36s cubic-bezier(0.22, 1, 0.36, 1);
}

.media-lb-leave-active {
    transition: opacity 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}

.media-lb-enter-from,
.media-lb-leave-to {
    opacity: 0;
}

.media-lb-enter-active .media-stage,
.media-lb-leave-active .media-stage {
    will-change: transform;
}

.media-lb-enter-active .media-stage {
    transition: transform 0.42s cubic-bezier(0.22, 1, 0.36, 1);
}

.media-lb-leave-active .media-stage {
    transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}

.media-lb-enter-from .media-stage {
    transform: scale3d(0.975, 0.975, 1);
}

.media-lb-leave-to .media-stage {
    transform: scale3d(0.985, 0.985, 1);
}

.media-lb-enter-active .media-chrome,
.media-lb-leave-active .media-chrome {
    transition: opacity 0.28s cubic-bezier(0.22, 1, 0.36, 1);
}

.media-lb-enter-from .media-chrome,
.media-lb-leave-to .media-chrome {
    opacity: 0;
}

.media-stage-asset {
    opacity: 1;
}

.media-stage-asset.absolute {
    opacity: 0;
    transition: opacity 0.32s ease;
}

.media-stage-asset.is-ready {
    opacity: 1;
}

/* Crossfade between items — no sideways slide, which fought the layout. */
.media-slide-next-enter-active,
.media-slide-prev-enter-active,
.media-slide-next-leave-active,
.media-slide-prev-leave-active {
    transition: opacity 0.22s ease;
}

.media-slide-next-leave-active,
.media-slide-prev-leave-active {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.media-slide-next-enter-from,
.media-slide-next-leave-to,
.media-slide-prev-enter-from,
.media-slide-prev-leave-to {
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .media-lb-enter-active,
    .media-lb-leave-active,
    .media-lb-enter-active .media-stage,
    .media-lb-leave-active .media-stage,
    .media-lb-enter-active .media-chrome,
    .media-lb-leave-active .media-chrome,
    .media-slide-next-enter-active,
    .media-slide-next-leave-active,
    .media-slide-prev-enter-active,
    .media-slide-prev-leave-active,
    .media-stage-asset:not(.media-stage-ph) {
        transition-duration: 0.01ms !important;
        transition-delay: 0ms !important;
    }

    .media-lb-enter-from .media-stage,
    .media-lb-leave-to .media-stage {
        transform: none;
    }
}
</style>
