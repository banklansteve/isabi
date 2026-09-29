<template>
    <Teleport to="body">
        <Transition name="admin-dialog">
            <div
                v-if="open"
                class="fixed inset-0 z-[80] flex items-end justify-center p-4 sm:items-center"
            >
                <button
                    type="button"
                    class="admin-dialog-backdrop absolute inset-0 bg-ink/40 backdrop-blur-[3px]"
                    aria-label="Close"
                    :disabled="processing"
                    @click="$emit('close')"
                />
                <form
                    class="admin-dialog-card relative z-[81] flex max-h-[min(92dvh,40rem)] w-full max-w-lg flex-col overflow-hidden rounded-[1.35rem] bg-white shadow-premium-ink"
                    @submit.prevent="submit"
                >
                    <div class="border-b border-ink/[0.06] px-5 py-4 sm:px-6">
                        <h3 class="text-[16px] font-bold tracking-tight text-ink">Close this chat?</h3>
                        <p class="mt-1 text-[13px] font-medium leading-relaxed text-ink/50">
                            Send a closing message first, then confirm the request is fully done. Closed chats leave the active queue and stay in history for {{ historyDays }} days.
                        </p>
                    </div>

                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4 sm:px-6">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Closing message</p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <button
                                    v-for="item in closeTemplates"
                                    :key="item.id"
                                    type="button"
                                    class="rounded-full px-3 py-1.5 text-[12px] font-semibold transition-colors"
                                    :class="selectedTemplateId === item.id ? 'bg-base-action text-white' : 'bg-pale text-ink/55 hover:bg-tint hover:text-deep'"
                                    @click="pickTemplate(item)"
                                >
                                    {{ item.title }}
                                </button>
                            </div>
                            <textarea
                                v-model="body"
                                rows="4"
                                required
                                placeholder="Tell the customer you’re closing the chat…"
                                class="mt-3 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                            />
                            <p v-if="alreadySent" class="mt-1.5 text-[12px] font-medium text-emerald-700">
                                A closing message was already sent — you can edit it or send as is.
                            </p>
                        </div>

                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Outcome</p>
                            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                <button
                                    type="button"
                                    class="rounded-xl px-3 py-3 text-left transition-colors ring-1"
                                    :class="outcome === 'completed' ? 'bg-tint/60 ring-base/25' : 'bg-white ring-ink/[0.08] hover:bg-pale'"
                                    @click="outcome = 'completed'"
                                >
                                    <span class="block text-[13px] font-bold text-ink">Completed</span>
                                    <span class="mt-0.5 block text-[12px] font-medium text-ink/45">Customer request fully handled</span>
                                </button>
                                <button
                                    type="button"
                                    class="rounded-xl px-3 py-3 text-left transition-colors ring-1"
                                    :class="outcome === 'abandoned' ? 'bg-tint/60 ring-base/25' : 'bg-white ring-ink/[0.08] hover:bg-pale'"
                                    @click="outcome = 'abandoned'"
                                >
                                    <span class="block text-[13px] font-bold text-ink">Abandoned</span>
                                    <span class="mt-0.5 block text-[12px] font-medium text-ink/45">Customer stopped responding</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-ink/[0.06] px-5 py-4 sm:px-6">
                        <button
                            type="button"
                            class="rounded-xl px-4 py-2.5 text-sm font-semibold text-ink/50 transition-colors hover:bg-pale disabled:opacity-50"
                            :disabled="processing"
                            @click="$emit('close')"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover disabled:opacity-50"
                            :disabled="processing || !body.trim()"
                        >
                            {{ processing ? 'Closing…' : 'Close chat' }}
                        </button>
                    </div>
                </form>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    templates: { type: Array, default: () => [] },
    alreadySent: { type: Boolean, default: false },
    historyDays: { type: Number, default: 30 },
    processing: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'confirm']);

const body = ref('');
const outcome = ref('completed');
const selectedTemplateId = ref(null);

const closeTemplates = computed(() =>
    (props.templates || []).filter((item) => (item.moment || 'general') === 'close'),
);

const pickTemplate = (item) => {
    selectedTemplateId.value = item.id;
    body.value = item.body || '';
};

watch(
    () => props.open,
    (open) => {
        if (!open) {
            return;
        }

        outcome.value = 'completed';
        const first = closeTemplates.value[0];
        if (first) {
            pickTemplate(first);
        } else {
            selectedTemplateId.value = null;
            body.value = '';
        }
    },
);

const submit = () => {
    if (!body.value.trim()) {
        return;
    }

    emit('confirm', {
        body: body.value.trim(),
        outcome: outcome.value,
    });
};
</script>

<style scoped>
.admin-dialog-enter-active,
.admin-dialog-leave-active {
    transition: opacity 0.18s ease;
}
.admin-dialog-enter-active .admin-dialog-card,
.admin-dialog-leave-active .admin-dialog-card {
    transition:
        transform 0.22s cubic-bezier(0.32, 0.72, 0, 1),
        opacity 0.18s ease;
}
.admin-dialog-enter-from,
.admin-dialog-leave-to {
    opacity: 0;
}
.admin-dialog-enter-from .admin-dialog-card,
.admin-dialog-leave-to .admin-dialog-card {
    opacity: 0;
    transform: translate3d(0, 12px, 0);
}
@media (min-width: 640px) {
    .admin-dialog-enter-from .admin-dialog-card,
    .admin-dialog-leave-to .admin-dialog-card {
        transform: translate3d(0, 8px, 0) scale(0.98);
    }
}
</style>
