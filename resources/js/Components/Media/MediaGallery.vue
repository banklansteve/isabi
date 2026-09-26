<template>
    <div>
        <ul
            v-if="items.length"
            class="job-gallery"
            :class="galleryClass"
        >
            <li
                v-for="(item, i) in items"
                :key="item.id ?? item.url ?? i"
                class="job-gallery__cell group relative overflow-hidden bg-zinc-100"
                :class="cellClass(i)"
            >
                <button
                    type="button"
                    class="relative block h-full w-full text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-base-action"
                    :aria-label="openLabel(item)"
                    @click="openAt(i)"
                >
                    <img
                        v-if="item.kind === 'image'"
                        :src="item.thumb_url || item.url"
                        :alt="item.original_name || 'Photo'"
                        loading="lazy"
                        decoding="async"
                        class="job-gallery__media absolute inset-0 h-full w-full object-cover transition-transform duration-700 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-[1.035]"
                    />
                    <template v-else>
                        <img
                            v-if="item.poster_url"
                            :src="item.poster_url"
                            :alt="item.original_name || 'Video'"
                            loading="lazy"
                            decoding="async"
                            class="job-gallery__media absolute inset-0 h-full w-full object-cover opacity-95 transition-transform duration-700 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-[1.035]"
                        />
                        <video
                            v-else
                            :src="item.url"
                            class="job-gallery__media pointer-events-none absolute inset-0 h-full w-full object-cover opacity-95"
                            muted
                            playsinline
                            preload="metadata"
                            tabindex="-1"
                        />
                        <span
                            class="pointer-events-none absolute inset-0 flex items-center justify-center bg-ink/20 transition-colors duration-300 group-hover:bg-ink/30"
                        >
                            <span
                                class="flex h-12 w-12 items-center justify-center rounded-full bg-white/95 text-ink shadow-lg ring-1 ring-ink/5 transition-transform duration-300 group-hover:scale-105"
                            >
                                <svg
                                    class="h-4 w-4 translate-x-[1px]"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M8 5.14v13.72a1 1 0 0 0 1.53.85l10.8-6.86a1 1 0 0 0 0-1.7L9.53 4.29A1 1 0 0 0 8 5.14z"
                                    />
                                </svg>
                            </span>
                        </span>
                    </template>

                    <span
                        class="pointer-events-none absolute inset-0 bg-gradient-to-t from-ink/50 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                        aria-hidden="true"
                    />
                    <span
                        class="pointer-events-none absolute bottom-3 left-3 rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.08em] text-zinc-600 opacity-0 shadow-sm ring-1 ring-zinc-200/80 transition-all duration-300 group-hover:opacity-100"
                    >
                        {{ item.kind === 'video' ? 'Video' : 'Photo' }}
                    </span>
                </button>
            </li>
        </ul>

        <MediaLightbox
            v-model:show="lightboxOpen"
            :items="items"
            :start-index="lightboxIndex"
            @close="lightboxOpen = false"
        />
    </div>
</template>

<script setup>
import MediaLightbox from '@/Components/Media/MediaLightbox.vue';
import { warmMediaItem } from '@/utils/mediaWarm';
import { computed, ref } from 'vue';

const props = defineProps({
    items: { type: Array, default: () => [] },
    /** Tailwind grid classes (used when layout is "grid") */
    gridClass: {
        type: String,
        default: 'grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4',
    },
    /**
     * "grid" — equal aspect tiles
     * "masonry" — editorial mosaic sized for finished-work showcases
     */
    layout: {
        type: String,
        default: 'grid',
        validator: (v) => ['grid', 'masonry'].includes(v),
    },
});

const lightboxOpen = ref(false);
const lightboxIndex = ref(0);

const isMasonry = computed(() => props.layout === 'masonry');
const count = computed(() => props.items.length);

const galleryClass = computed(() => {
    if (!isMasonry.value) {
        return ['grid', props.gridClass];
    }

    const n = count.value;
    if (n <= 1) return 'job-gallery--solo';
    if (n === 2) return 'job-gallery--duo';
    if (n === 3) return 'job-gallery--trio';
    if (n === 4) return 'job-gallery--quad';
    return 'job-gallery--mosaic';
});

const cellClass = (i) => {
    if (!isMasonry.value) {
        return [
            'rounded-xl',
            count.value === 1 ? 'aspect-[16/10] sm:aspect-[21/9]' : 'aspect-square',
        ];
    }

    const n = count.value;
    if (n <= 1) return 'job-gallery__cell--hero rounded-[1.15rem]';
    if (n === 2) return 'rounded-[1.1rem]';
    if (n === 3) return i === 0 ? 'job-gallery__cell--lead rounded-[1.1rem]' : 'rounded-[1.1rem]';
    if (n === 4) return 'rounded-[1.05rem]';

    // 5+: featured first tile spans larger
    if (i === 0) return 'job-gallery__cell--feature rounded-[1.1rem]';
    return 'rounded-[1.05rem]';
};

const openLabel = (item) => {
    const kind = item.kind === 'video' ? 'video' : 'photo';
    return item.original_name ? `Open ${kind}: ${item.original_name}` : `Open ${kind}`;
};

const openAt = (i) => {
    lightboxIndex.value = i;
    warmMediaItem(props.items[i]);
    lightboxOpen.value = true;
};

defineExpose({ openAt });
</script>

<style scoped>
.job-gallery--solo {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.75rem;
}

.job-gallery--solo .job-gallery__cell--hero {
    aspect-ratio: 16 / 10;
}

@media (min-width: 640px) {
    .job-gallery--solo .job-gallery__cell--hero {
        aspect-ratio: 21 / 9;
    }
}

.job-gallery--duo {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.65rem;
}

.job-gallery--duo .job-gallery__cell {
    aspect-ratio: 4 / 3;
}

@media (min-width: 640px) {
    .job-gallery--duo {
        gap: 0.85rem;
    }
}

.job-gallery--trio {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: auto auto;
    gap: 0.65rem;
}

.job-gallery--trio .job-gallery__cell--lead {
    grid-column: 1 / -1;
    aspect-ratio: 16 / 10;
}

.job-gallery--trio .job-gallery__cell:not(.job-gallery__cell--lead) {
    aspect-ratio: 4 / 3;
}

@media (min-width: 768px) {
    .job-gallery--trio {
        grid-template-columns: 1.35fr 1fr;
        grid-template-rows: 1fr 1fr;
        gap: 0.85rem;
        min-height: 22rem;
    }

    .job-gallery--trio .job-gallery__cell--lead {
        grid-column: 1;
        grid-row: 1 / -1;
        aspect-ratio: auto;
        min-height: 100%;
    }

    .job-gallery--trio .job-gallery__cell:not(.job-gallery__cell--lead) {
        aspect-ratio: auto;
    }
}

.job-gallery--quad {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.65rem;
}

.job-gallery--quad .job-gallery__cell {
    aspect-ratio: 4 / 3;
}

@media (min-width: 640px) {
    .job-gallery--quad {
        gap: 0.85rem;
    }

    .job-gallery--quad .job-gallery__cell {
        aspect-ratio: 5 / 4;
    }
}

.job-gallery--mosaic {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.65rem;
}

.job-gallery--mosaic .job-gallery__cell {
    aspect-ratio: 1 / 1;
}

.job-gallery--mosaic .job-gallery__cell--feature {
    grid-column: 1 / -1;
    aspect-ratio: 16 / 10;
}

@media (min-width: 768px) {
    .job-gallery--mosaic {
        grid-template-columns: repeat(3, 1fr);
        grid-auto-rows: minmax(9.5rem, auto);
        gap: 0.85rem;
    }

    .job-gallery--mosaic .job-gallery__cell {
        aspect-ratio: 4 / 3;
    }

    .job-gallery--mosaic .job-gallery__cell--feature {
        grid-column: span 2;
        grid-row: span 2;
        aspect-ratio: auto;
        min-height: 100%;
    }
}
</style>
