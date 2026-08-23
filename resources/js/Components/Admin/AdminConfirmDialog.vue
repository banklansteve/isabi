<template>
    <Teleport to="body">
        <Transition name="admin-dialog">
            <div
                v-if="open"
                class="fixed inset-0 z-[80] flex items-end justify-center p-4 sm:items-center"
            >
                <button
                    type="button"
                    class="absolute inset-0 bg-ink/40 backdrop-blur-[3px]"
                    aria-label="Close"
                    @click="$emit('close')"
                />
                <form
                    class="admin-dialog-card relative z-[81] w-full max-w-md rounded-[1.35rem] bg-white p-5 shadow-premium-ink sm:p-6"
                    @submit.prevent="submit"
                >
                    <h3 class="text-[16px] font-bold tracking-tight text-ink">{{ title }}</h3>
                    <p v-if="description" class="mt-1 text-[13px] font-medium leading-relaxed text-ink/50">
                        {{ description }}
                    </p>

                    <slot />

                    <label v-if="requireReason" class="mt-4 block">
                        <span class="text-[12px] font-semibold text-ink/50">Reason</span>
                        <textarea
                            v-model="reason"
                            rows="3"
                            required
                            :placeholder="reasonPlaceholder"
                            class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                        />
                    </label>

                    <label v-if="confirmPhrase" class="mt-3 block">
                        <span class="text-[12px] font-semibold text-ink/50">
                            Type <span class="font-bold text-ink">{{ confirmPhrase }}</span> to confirm
                        </span>
                        <input
                            v-model="confirmation"
                            type="text"
                            required
                            class="mt-1.5 w-full rounded-xl border border-ink/10 px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                        />
                    </label>

                    <div class="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-xl px-4 py-2.5 text-sm font-semibold text-ink/50 transition-colors duration-150 hover:bg-pale"
                            @click="$emit('close')"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-150 disabled:opacity-50"
                            :class="tone === 'danger' ? 'bg-red-600 hover:bg-red-700' : 'bg-base-action shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] hover:bg-base-hover'"
                            :disabled="processing || phraseMismatch"
                        >
                            {{ processing ? 'Working…' : confirmLabel }}
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
    title: { type: String, required: true },
    description: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Confirm' },
    tone: { type: String, default: 'default' },
    requireReason: { type: Boolean, default: true },
    reasonPlaceholder: { type: String, default: 'What happened, in a sentence…' },
    confirmPhrase: { type: String, default: '' },
    processing: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'confirm']);

const reason = ref('');
const confirmation = ref('');

const phraseMismatch = computed(() => {
    if (!props.confirmPhrase) {
        return false;
    }

    return confirmation.value.trim().toLowerCase() !== props.confirmPhrase.trim().toLowerCase();
});

watch(
    () => props.open,
    (open) => {
        if (open) {
            reason.value = '';
            confirmation.value = '';
        }
    },
);

const submit = () => {
    emit('confirm', {
        reason: reason.value.trim(),
        confirmation: confirmation.value.trim(),
    });
};
</script>

<style>
.admin-dialog-enter-active,
.admin-dialog-leave-active {
    transition: opacity 0.32s cubic-bezier(0.32, 0.72, 0, 1);
}
.admin-dialog-enter-active .admin-dialog-card,
.admin-dialog-leave-active .admin-dialog-card {
    transition: transform 0.36s cubic-bezier(0.32, 0.72, 0, 1), opacity 0.32s cubic-bezier(0.32, 0.72, 0, 1);
}
.admin-dialog-enter-from,
.admin-dialog-leave-to {
    opacity: 0;
}
.admin-dialog-enter-from .admin-dialog-card,
.admin-dialog-leave-to .admin-dialog-card {
    opacity: 0;
    transform: translate3d(0, 1.25rem, 0) scale(0.98);
}
</style>
