<template>
    <aside
        v-if="tasks.length || pages.length"
        class="sticky top-[7.75rem] hidden max-h-[calc(100dvh-9rem)] w-56 shrink-0 overflow-y-auto lg:block"
        aria-label="Operations"
    >
        <nav class="space-y-3">
            <div class="rounded-2xl bg-white p-1.5 shadow-premium ring-1 ring-ink/[0.05]">
                <div class="flex items-center justify-between px-2.5 pb-1 pt-1.5">
                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-ink/35">Tasks</p>
                    <Link
                        :href="route('admin.tasks')"
                        prefetch
                        cache-for="5m"
                        :show-progress="false"
                        class="text-[11px] font-bold text-base-action hover:text-base-hover"
                    >
                        All
                    </Link>
                </div>

                <p v-if="!tasks.length" class="px-2.5 py-3 text-[12px] font-medium leading-relaxed text-ink/40">
                    You’re clear for now.
                </p>

                <button
                    v-for="item in tasks"
                    :key="item.key"
                    type="button"
                    class="flex w-full items-start gap-2.5 rounded-xl px-2.5 py-2 text-left transition-colors hover:bg-pale"
                    @click="openTask(item)"
                >
                    <i
                        :class="item.icon || 'ti ti-circle'"
                        class="mt-0.5 text-[1.05rem] text-ink/35"
                        aria-hidden="true"
                    />
                    <span class="min-w-0 flex-1">
                        <span class="flex items-start gap-1.5">
                            <span class="block truncate text-[13px] font-semibold text-ink">{{ item.title }}</span>
                            <span
                                v-if="item.unread"
                                class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-base-action"
                            />
                        </span>
                        <span class="mt-0.5 block truncate text-[11px] font-medium text-ink/40">
                            {{ item.subtitle }}
                        </span>
                    </span>
                </button>
            </div>

            <div v-if="pages.length" class="rounded-2xl bg-white p-1.5 shadow-premium ring-1 ring-ink/[0.05]">
                <p class="px-2.5 pb-1 pt-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-ink/35">
                    Pages
                </p>
                <Link
                    v-for="item in pages"
                    :key="item.key"
                    :href="item.href"
                    prefetch
                    cache-for="5m"
                    :show-progress="false"
                    class="flex items-center gap-2.5 rounded-xl px-2.5 py-2 text-[13px] transition-colors"
                    :class="[
                        item.active ? 'bg-tint text-deep' : 'text-ink/50 hover:bg-pale hover:text-ink',
                        item.unread ? 'font-extrabold text-ink' : 'font-semibold',
                    ]"
                >
                    <i
                        :class="[item.icon, item.active ? 'text-base-action' : 'text-ink/35']"
                        class="text-[1.05rem]"
                        aria-hidden="true"
                    />
                    <span class="min-w-0 flex-1 truncate">{{ item.label }}</span>
                    <span
                        v-if="item.badge"
                        class="rounded-full px-1.5 py-0.5 text-[10px] font-extrabold leading-none"
                        :class="item.unread ? 'bg-base-action text-white' : 'bg-pale text-ink/50'"
                    >
                        {{ formatBadgeCount(item.badge) }}
                    </span>
                </Link>
            </div>
        </nav>
    </aside>
</template>

<script setup>
import { useOpsAttention } from '@/Composables/useOpsAttention';
import { formatBadgeCount } from '@/utils/opsStatus';
import { Link, router } from '@inertiajs/vue3';

defineProps({
    tasks: { type: Array, default: () => [] },
    pages: { type: Array, default: () => [] },
});

const { markRead } = useOpsAttention();

const openTask = async (item) => {
    if (!item?.href) {
        return;
    }

    await markRead(item);
    router.visit(item.href);
};
</script>
