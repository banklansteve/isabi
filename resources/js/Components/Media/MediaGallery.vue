<template>
    <div>
        <ul
            v-if="items.length"
            :class="listClass"
        >
            <li
                v-for="(item, i) in items"
                :key="item.id ?? item.url ?? i"
                class="group relative overflow-hidden bg-pale ring-1 ring-ink/[0.06]"
                :class="itemClass(i)"
            >
                <button
                    type="button"
                    class="relative block h-full w-full text-left"
                    :aria-label="openLabel(item)"
                    @click="openAt(i)"
                >
                    <img
                        v-if="item.kind === 'image'"
                        :src="item.thumb_url || item.url"
                        :alt="item.original_name || 'Photo'"
                        loading="lazy"
                        decoding="async"
                        class="w-full object-cover transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-[1.02]"
                        :class="mediaFillClass"
                    />
                    <template v-else>
                        <img
                            v-if="item.poster_url"
                            :src="item.poster_url"
                            :alt="item.original_name || 'Video'"
                            loading="lazy"
                            decoding="async"
                            class="w-full object-cover opacity-90"
                            :class="mediaFillClass"
                        />
                        <video
                            v-else
                            :src="item.url"
                            class="pointer-events-none w-full object-cover opacity-90"
                            :class="mediaFillClass"
                            muted
                            playsinline
                            preload="metadata"
                            tabindex="-1"
                        />
                        <span
                            class="pointer-events-none absolute inset-0 flex items-center justify-center bg-ink/25 transition-colors duration-200 group-hover:bg-ink/35"
                        >
                            <span
                                class="flex h-11 w-11 items-center justify-center rounded-full bg-white/95 text-ink shadow-lg ring-1 ring-ink/5"
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
                        class="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-ink/45 to-transparent opacity-0 transition-opacity duration-200 group-hover:opacity-100"
                        aria-hidden="true"
                    />
                    <span
                        class="pointer-events-none absolute bottom-2 left-2 rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-ink/70 opacity-0 shadow-sm transition-opacity duration-200 group-hover:opacity-100"
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
     * "masonry" — CSS-column masonry on mobile, clean multi-col grid from md up
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

const listClass = computed(() => {
    if (!isMasonry.value) {
        return ['grid', props.gridClass];
    }

    if (count.value <= 1) {
        return 'grid grid-cols-1 gap-3 sm:gap-4';
    }

    return [
        'columns-2 gap-x-3 sm:gap-x-4',
        'md:columns-auto md:grid md:gap-4',
        count.value === 2 ? 'md:grid-cols-2' : '',
        count.value === 3 ? 'md:grid-cols-3' : '',
        count.value >= 4 ? 'md:grid-cols-2 lg:grid-cols-3' : '',
    ]
        .filter(Boolean)
        .join(' ');
});

/** Fill the cell on desktop grid; natural height for mobile masonry. */
const mediaFillClass = computed(() => {
    if (!isMasonry.value) {
        return 'h-full';
    }
    if (count.value <= 1) {
        return 'h-full';
    }
    return 'h-auto md:h-full md:absolute md:inset-0';
});

const itemClass = (i) => {
    if (!isMasonry.value) {
        return [
            'rounded-xl',
            count.value === 1 ? 'aspect-[16/10] sm:aspect-[21/9]' : 'aspect-square',
        ];
    }

    if (count.value === 1) {
        return 'rounded-2xl aspect-[16/10] sm:aspect-[21/9]';
    }

    const featured = count.value >= 4 && i === 0;
    return [
        'mb-3 break-inside-avoid rounded-2xl last:mb-0 md:mb-0',
        featured ? 'md:col-span-2 md:row-span-2 md:aspect-square' : 'md:aspect-[4/3]',
    ];
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
