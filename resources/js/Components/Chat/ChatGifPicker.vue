<template>
    <div class="relative">
        <button
            type="button"
            class="tap-target flex h-10 w-10 items-center justify-center rounded-xl bg-pale text-ink/45 hover:text-ink"
            :aria-expanded="open"
            aria-label="GIF"
            @click="toggle"
        >
            <span class="text-[11px] font-extrabold tracking-wide">GIF</span>
        </button>

        <div
            v-if="open"
            class="absolute bottom-[calc(100%+0.5rem)] left-0 z-30 w-[22rem] overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.08] sm:w-[24rem]"
        >
            <div class="border-b border-ink/[0.06] p-2.5">
                <input
                    v-model="query"
                    type="search"
                    placeholder="Search GIFs…"
                    class="w-full rounded-xl border border-ink/10 bg-[#F4F6FA] px-3 py-2 text-[13px] font-medium outline-none focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                    @input="onSearch"
                />
            </div>
            <div class="grid max-h-64 grid-cols-2 gap-2 overflow-y-auto p-2.5">
                <button
                    v-for="gif in visible"
                    :key="gif.id"
                    type="button"
                    class="overflow-hidden rounded-xl bg-pale ring-1 ring-ink/[0.05] transition hover:ring-base/30"
                    @click="pick(gif)"
                >
                    <img :src="gif.preview" :alt="gif.name" class="h-24 w-full object-cover" loading="lazy" />
                </button>
                <p v-if="!visible.length" class="col-span-2 px-2 py-6 text-center text-[12px] font-medium text-ink/40">
                    No GIFs match that search.
                </p>
            </div>
            <p class="border-t border-ink/[0.06] px-3 py-2 text-[10px] font-medium text-ink/35">
                Curated reactions — keep it work-appropriate.
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const emit = defineEmits(['pick']);

const open = ref(false);
const query = ref('');

const catalog = [
    { id: 'thumbs', name: 'Thumbs up', preview: 'https://media.giphy.com/media/111ebonMs90YLu/giphy.gif', url: 'https://media.giphy.com/media/111ebonMs90YLu/giphy.gif' },
    { id: 'clap', name: 'Clap', preview: 'https://media.giphy.com/media/7rj2ZgRRHagzlCpd6s/giphy.gif', url: 'https://media.giphy.com/media/7rj2ZgRRHagzlCpd6s/giphy.gif' },
    { id: 'check', name: 'Done', preview: 'https://media.giphy.com/media/3o7abKhOpu0NwenH3O/giphy.gif', url: 'https://media.giphy.com/media/3o7abKhOpu0NwenH3O/giphy.gif' },
    { id: 'wave', name: 'Wave', preview: 'https://media.giphy.com/media/xT9IgG50Fb7Mi0prBC/giphy.gif', url: 'https://media.giphy.com/media/xT9IgG50Fb7Mi0prBC/giphy.gif' },
    { id: 'think', name: 'Thinking', preview: 'https://media.giphy.com/media/d3mlE7uhX8KFgEmY/giphy.gif', url: 'https://media.giphy.com/media/d3mlE7uhX8KFgEmY/giphy.gif' },
    { id: 'fire', name: 'Fire', preview: 'https://media.giphy.com/media/l0MYt5jPR6QX5pnqM/giphy.gif', url: 'https://media.giphy.com/media/l0MYt5jPR6QX5pnqM/giphy.gif' },
];

const visible = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) {
        return catalog;
    }

    return catalog.filter((gif) => gif.name.toLowerCase().includes(q) || gif.id.includes(q));
});

const toggle = () => {
    open.value = !open.value;
};

const onSearch = () => {};

const pick = (gif) => {
    emit('pick', { url: gif.url, name: gif.name });
    open.value = false;
    query.value = '';
};

const onDoc = (event) => {
    if (!open.value) {
        return;
    }
    if (!event.target?.closest?.('.relative')) {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('pointerdown', onDoc));
onUnmounted(() => document.removeEventListener('pointerdown', onDoc));
</script>
