<template>
    <div class="relative">
        <button
            type="button"
            class="tap-target flex h-10 w-10 items-center justify-center rounded-xl bg-pale text-ink/45 hover:text-ink"
            :aria-expanded="open"
            aria-label="Emoji"
            @click="toggle"
        >
            <i class="ti ti-mood-smile text-lg" aria-hidden="true" />
        </button>

        <div
            v-if="open"
            class="absolute bottom-[calc(100%+0.5rem)] left-0 z-30 w-[18.5rem] overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.08]"
        >
            <div class="flex items-center justify-between border-b border-ink/[0.06] px-3 py-2">
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Emoji</p>
                <button type="button" class="text-[11px] font-semibold text-ink/40 hover:text-ink" @click="open = false">
                    Close
                </button>
            </div>
            <div class="grid max-h-52 grid-cols-8 gap-0.5 overflow-y-auto p-2">
                <button
                    v-for="emoji in emojis"
                    :key="emoji"
                    type="button"
                    class="flex h-9 items-center justify-center rounded-lg text-lg transition hover:bg-pale"
                    @click="pick(emoji)"
                >
                    {{ emoji }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';

const emit = defineEmits(['pick']);

const open = ref(false);
const emojis = [
    '😀', '😁', '😂', '😊', '😍', '🤩', '😎', '🤔',
    '😅', '😢', '😭', '😡', '👍', '👎', '👏', '🙏',
    '🔥', '✨', '✅', '❌', '⚠️', '💡', '📌', '📎',
    '🎉', '💪', '🤝', '💬', '📩', '🕒', '📍', '❤️',
];

const toggle = () => {
    open.value = !open.value;
};

const pick = (emoji) => {
    emit('pick', emoji);
    open.value = false;
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
