<template>
    <ul
        v-if="visible.length"
        class="grid h-full w-full gap-1"
        :style="gridStyle"
    >
        <li
            v-for="(item, i) in visible"
            :key="item.id ?? item.url ?? i"
            class="group relative min-h-0 overflow-hidden bg-ink/[0.04]"
            :style="{ gridArea: layout.areas[i] }"
        >
            <button
                type="button"
                class="block h-full w-full text-left"
                :aria-label="tileLabel(item, i)"
                @click="$emit('open', i)"
            >
                <img
                    v-if="item.kind === 'image'"
                    :src="item.thumb_url || item.url"
                    :alt="item.original_name || 'Photo of finished work'"
                    :loading="i < 2 ? 'eager' : 'lazy'"
                    :fetchpriority="i === 0 ? 'high' : 'auto'"
                    decoding="async"
                    class="h-full w-full object-cover transition-[transform,opacity] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-[1.03]"
                    :class="loaded[i] ? 'opacity-100' : 'opacity-0'"
                    draggable="false"
                    @load="loaded[i] = true"
                    @error="loaded[i] = true"
                />
                <template v-else>
                    <img
                        v-if="item.poster_url"
                        :src="item.poster_url"
                        :alt="item.original_name || 'Video of finished work'"
                        loading="lazy"
                        decoding="async"
                        class="h-full w-full object-cover transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-[1.03]"
                        draggable="false"
                    />
                    <video
                        v-else
                        :src="item.url"
                        class="pointer-events-none h-full w-full object-cover transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-[1.03]"
                        muted
                        playsinline
                        preload="metadata"
                        tabindex="-1"
                    />
                    <span
                        class="pointer-events-none absolute inset-0 flex items-center justify-center bg-ink/20"
                    >
                        <span
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-white/95 text-ink shadow-lg ring-1 ring-ink/5 transition-transform duration-300 group-hover:scale-105"
                        >
                            <svg class="h-4 w-4 translate-x-[1px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M8 5.14v13.72a1 1 0 0 0 1.53.85l10.8-6.86a1 1 0 0 0 0-1.7L9.53 4.29A1 1 0 0 0 8 5.14z" />
                            </svg>
                        </span>
                    </span>
                </template>

                <!-- Hover veil -->
                <span
                    class="pointer-events-none absolute inset-0 bg-gradient-to-t from-ink/50 via-ink/0 to-ink/0 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                    aria-hidden="true"
                />

                <!-- Remaining count on the final tile -->
                <span
                    v-if="i === visible.length - 1 && overflowCount > 0"
                    class="pointer-events-none absolute inset-0 flex items-center justify-center bg-ink/60 backdrop-blur-[2px] transition-colors duration-300 group-hover:bg-ink/70"
                >
                    <span class="font-editorial text-2xl font-semibold text-white sm:text-3xl">
                        +{{ overflowCount }}
                    </span>
                </span>
            </button>
        </li>
    </ul>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';

/**
 * Mosaic layouts keyed by visible tile count.
 * `areas` use the grid-area shorthand: row-start / col-start / row-end / col-end.
 */
const LAYOUTS = {
    1: { cols: 1, rows: 1, areas: ['1/1/2/2'] },
    2: { cols: 2, rows: 1, areas: ['1/1/2/2', '1/2/2/3'] },
    3: { cols: 3, rows: 2, areas: ['1/1/3/3', '1/3/2/4', '2/3/3/4'] },
    4: { cols: 2, rows: 2, areas: ['1/1/2/2', '1/2/2/3', '2/1/3/2', '2/2/3/3'] },
    5: {
        cols: 3,
        rows: 2,
        areas: ['1/1/2/3', '1/3/2/4', '2/1/3/2', '2/2/3/3', '2/3/3/4'],
    },
    6: {
        cols: 3,
        rows: 2,
        areas: ['1/1/2/2', '1/2/2/3', '1/3/2/4', '2/1/3/2', '2/2/3/3', '2/3/3/4'],
    },
};

const MAX_TILES = 6;

const props = defineProps({
    items: { type: Array, default: () => [] },
});

defineEmits(['open']);

const visible = computed(() => props.items.slice(0, MAX_TILES));

/** Tiles fade in once decoded so a slow image never shows as a blank cell. */
const loaded = reactive({});

watch(visible, () => {
    Object.keys(loaded).forEach((key) => delete loaded[key]);
});

const overflowCount = computed(() => Math.max(0, props.items.length - visible.value.length));

const layout = computed(() => LAYOUTS[visible.value.length] || LAYOUTS[1]);

const gridStyle = computed(() => ({
    gridTemplateColumns: `repeat(${layout.value.cols}, minmax(0, 1fr))`,
    gridTemplateRows: `repeat(${layout.value.rows}, minmax(0, 1fr))`,
}));

const tileLabel = (item, i) => {
    const kind = item.kind === 'video' ? 'video' : 'photo';
    if (i === visible.value.length - 1 && overflowCount.value > 0) {
        return `View all ${props.items.length} items`;
    }
    return `Open ${kind}${item.original_name ? `: ${item.original_name}` : ''}`;
};
</script>
