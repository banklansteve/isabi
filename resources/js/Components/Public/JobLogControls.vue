<template>
    <div class="rounded-xl bg-white p-3 shadow-premium ring-1 ring-ink/[0.05] sm:rounded-2xl sm:p-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="relative min-w-0 flex-1">
                <i
                    class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/35"
                    aria-hidden="true"
                />
                <input
                    :value="search"
                    type="search"
                    placeholder="Search jobs and reviews — e.g. staircase, rewiring"
                    class="w-full rounded-xl border border-ink/10 bg-pale py-3 pe-3 ps-10 text-sm font-medium text-ink outline-none placeholder:text-ink/35 focus:border-base focus:bg-white focus:ring-2 focus:ring-base/15"
                    aria-label="Search jobs and reviews"
                    @input="$emit('update:search', $event.target.value)"
                />
            </div>

            <div class="flex items-center gap-2">
                <label class="sr-only" for="job-log-sort">Sort jobs</label>
                <div class="relative min-w-[11.5rem] flex-1 lg:flex-none">
                    <i
                        class="ti ti-arrows-sort pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink/35"
                        aria-hidden="true"
                    />
                    <select
                        id="job-log-sort"
                        :value="sort"
                        class="w-full appearance-none rounded-xl border border-ink/10 bg-pale py-3 pe-8 ps-9 text-xs font-bold text-ink outline-none focus:border-base focus:bg-white focus:ring-2 focus:ring-base/15"
                        @change="$emit('update:sort', $event.target.value)"
                    >
                        <option
                            v-for="option in sortOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <i
                        class="ti ti-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-ink/30"
                        aria-hidden="true"
                    />
                </div>

                <button
                    v-if="hasActiveFilters"
                    type="button"
                    class="tap-target shrink-0 rounded-xl px-3 py-3 text-xs font-bold text-ink/45 transition-colors hover:bg-pale hover:text-ink"
                    @click="$emit('clear')"
                >
                    Clear
                </button>
            </div>
        </div>

        <!-- Category facets double as the quick-jump for long logs -->
        <div v-if="categories.length > 1" class="mt-3.5 border-t border-ink/[0.06] pt-3.5">
            <div class="flex flex-wrap gap-1.5">
                <button
                    type="button"
                    class="tap-target rounded-full px-3 py-1.5 text-xs font-bold transition-colors"
                    :class="
                        category === ''
                            ? 'bg-deep text-white'
                            : 'bg-pale text-ink/55 hover:bg-tint hover:text-deep'
                    "
                    @click="$emit('update:category', '')"
                >
                    All
                    <span class="ms-1 tabular-nums opacity-60">{{ totalCount }}</span>
                </button>
                <button
                    v-for="facet in categories"
                    :key="facet.value"
                    type="button"
                    class="tap-target rounded-full px-3 py-1.5 text-xs font-bold transition-colors"
                    :class="
                        category === facet.value
                            ? 'bg-deep text-white'
                            : 'bg-pale text-ink/55 hover:bg-tint hover:text-deep'
                    "
                    @click="$emit('update:category', category === facet.value ? '' : facet.value)"
                >
                    {{ facet.label }}
                    <span class="ms-1 tabular-nums opacity-60">{{ facet.count }}</span>
                </button>
            </div>
        </div>

        <p
            v-if="hasActiveFilters"
            class="mt-3 text-xs font-semibold text-ink/45"
            role="status"
            aria-live="polite"
        >
            Showing {{ resultCount }} of {{ totalCount }} job{{ totalCount === 1 ? '' : 's' }}
        </p>
    </div>
</template>

<script setup>
defineProps({
    search: { type: String, default: '' },
    sort: { type: String, default: 'recent' },
    category: { type: String, default: '' },
    categories: { type: Array, default: () => [] },
    sortOptions: { type: Array, default: () => [] },
    resultCount: { type: Number, default: 0 },
    totalCount: { type: Number, default: 0 },
    hasActiveFilters: { type: Boolean, default: false },
});

defineEmits(['update:search', 'update:sort', 'update:category', 'clear']);
</script>
