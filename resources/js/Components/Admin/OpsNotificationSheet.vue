<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
        >
            <button
                v-if="open"
                type="button"
                class="fixed inset-0 z-[60] bg-ink/40 backdrop-blur-[2px]"
                aria-label="Close notifications"
                @click="$emit('close')"
            />
        </Transition>

        <Transition
            enter-active-class="transition-transform duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]"
            enter-from-class="translate-y-full sm:translate-y-0 sm:translate-x-full"
            leave-active-class="transition-transform duration-200 ease-in"
            leave-to-class="translate-y-full sm:translate-y-0 sm:translate-x-full"
        >
            <aside
                v-if="open"
                class="fixed inset-x-0 bottom-0 z-[70] max-h-[86dvh] overflow-hidden rounded-t-3xl bg-white shadow-[0_-20px_60px_-24px_rgba(7,20,39,0.35)] sm:inset-y-0 sm:left-auto sm:right-0 sm:w-[24rem] sm:max-h-none sm:rounded-none sm:border-l sm:border-ink/[0.06]"
                role="dialog"
                aria-labelledby="ops-inbox-title"
            >
                <div class="flex items-center justify-between px-5 py-4">
                    <p id="ops-inbox-title" class="text-[15px] font-bold text-ink">Notifications</p>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="items.length"
                            type="button"
                            class="rounded-full bg-tint px-2.5 py-1 text-[11px] font-bold text-deep"
                            @click="markAll"
                        >
                            Mark all read
                        </button>
                        <button
                            type="button"
                            class="tap-target flex h-10 w-10 items-center justify-center rounded-xl text-ink/45 hover:bg-pale hover:text-ink"
                            aria-label="Close notifications"
                            @click="$emit('close')"
                        >
                            <i class="ti ti-x text-lg" aria-hidden="true" />
                        </button>
                    </div>
                </div>
                <div class="border-t border-ink/[0.06]" />

                <div class="overflow-y-auto px-3 py-3" style="max-height: calc(86dvh - 4.5rem)">
                    <p v-if="!items.length" class="px-3 py-12 text-center">
                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-pale text-ink/30">
                            <i class="ti ti-bell-off text-xl" aria-hidden="true" />
                        </span>
                        <span class="mt-3 block text-sm font-bold text-ink">You’re all caught up</span>
                        <span class="mt-1 block text-[13px] font-medium leading-relaxed text-ink/45">
                            New work that needs you will show up here.
                        </span>
                    </p>
                    <button
                        v-for="item in items"
                        :key="item.key"
                        type="button"
                        class="mb-1.5 flex w-full items-start gap-3 rounded-2xl px-3 py-3.5 text-left hover:bg-pale active:bg-tint"
                        @click="openItem(item)"
                    >
                        <span
                            class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-base"
                            :class="feedToneClass(item.tone)"
                        >
                            <i :class="item.icon || 'ti ti-bell'" aria-hidden="true" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-bold leading-snug text-ink">{{ item.title }}</span>
                            <span class="mt-0.5 block text-[13px] font-medium text-ink/45">{{ item.subtitle }}</span>
                        </span>
                        <i class="ti ti-chevron-right mt-1 shrink-0 text-ink/25" aria-hidden="true" />
                    </button>
                </div>
            </aside>
        </Transition>
    </Teleport>
</template>

<script setup>
import { useOpsAttention } from '@/Composables/useOpsAttention';
import { feedToneClass } from '@/utils/opsStatus';
import { router } from '@inertiajs/vue3';

defineProps({
    open: { type: Boolean, default: false },
    items: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);
const { markRead, markAll: markAllRead } = useOpsAttention();

const openItem = async (item) => {
    if (!item?.href) {
        return;
    }

    await markRead(item);
    emit('close');
    router.visit(item.href);
};

const markAll = async () => {
    await markAllRead();
    emit('close');
};
</script>
