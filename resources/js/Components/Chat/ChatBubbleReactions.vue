<template>
    <div
        class="group/react relative"
        :class="align === 'end' ? 'items-end' : 'items-start'"
    >
        <div class="relative inline-flex max-w-full flex-col" :class="align === 'end' ? 'items-end' : 'items-start'">
            <div
                class="pointer-events-none absolute -top-3 z-10 flex opacity-0 transition-opacity duration-150 group-hover/react:pointer-events-auto group-hover/react:opacity-100 group-focus-within/react:pointer-events-auto group-focus-within/react:opacity-100"
                :class="align === 'end' ? 'right-2' : 'left-2'"
            >
                <div class="flex items-center gap-0.5 rounded-full bg-white px-1.5 py-1 shadow-premium ring-1 ring-ink/[0.08]">
                    <button
                        v-for="emoji in quick"
                        :key="emoji"
                        type="button"
                        class="flex h-7 w-7 items-center justify-center rounded-full text-[15px] transition hover:bg-pale"
                        :aria-label="`React ${emoji}`"
                        @click.stop="toggle(emoji)"
                    >
                        {{ emoji }}
                    </button>
                </div>
            </div>

            <slot />

            <div
                v-if="localReactions.length"
                class="mt-1 flex max-w-full flex-wrap gap-1"
                :class="align === 'end' ? 'justify-end' : 'justify-start'"
            >
                <button
                    v-for="item in localReactions"
                    :key="item.emoji"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[12px] font-semibold ring-1 transition"
                    :class="item.mine
                        ? 'bg-tint text-deep ring-base/20'
                        : 'bg-white text-ink ring-ink/[0.08] hover:bg-pale'"
                    @click.stop="toggle(item.emoji)"
                >
                    <span>{{ item.emoji }}</span>
                    <span v-if="item.count > 1">{{ item.count }}</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { ref, watch } from 'vue';

const props = defineProps({
    reactions: { type: Array, default: () => [] },
    endpoint: { type: String, required: true },
    align: { type: String, default: 'start' },
    dark: { type: Boolean, default: false },
});

const emit = defineEmits(['updated']);

const quick = ['👍', '❤️', '😂', '😮', '😢', '🙏'];
const localReactions = ref([...(props.reactions || [])]);
const busy = ref(false);

watch(
    () => props.reactions,
    (value) => {
        localReactions.value = [...(value || [])];
    },
);

const toggle = async (emoji) => {
    if (busy.value || !props.endpoint) {
        return;
    }

    busy.value = true;
    try {
        const { data } = await axios.post(
            props.endpoint,
            { emoji },
            { headers: { Accept: 'application/json' } },
        );
        localReactions.value = data.reactions || data || [];
        emit('updated', localReactions.value);
    } finally {
        busy.value = false;
    }
};
</script>
