<template>
    <Dropdown align="right" width="80" content-classes="overflow-hidden rounded-2xl bg-white p-0">
        <template #trigger>
            <button
                type="button"
                class="tap-target relative flex h-10 w-10 items-center justify-center rounded-full text-ink/55 transition-colors duration-200 hover:bg-pale hover:text-ink focus-visible:ring-2 focus-visible:ring-base/30"
                aria-label="Notifications"
            >
                <i class="ti ti-bell text-xl" aria-hidden="true" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute right-1.5 top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-coral px-1 text-[10px] font-bold text-white"
                >
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
            </button>
        </template>

        <template #content>
            <div class="w-80">
                <div class="flex items-center justify-between px-4 py-3.5">
                    <p class="text-sm font-bold text-ink">Notifications</p>
                    <span
                        v-if="unreadCount > 0"
                        class="rounded-full bg-tint px-2 py-0.5 text-[11px] font-bold text-deep"
                    >
                        {{ unreadCount }} new
                    </span>
                </div>
                <div class="border-t border-ink/10" />

                <div v-if="items.length" class="max-h-80 overflow-y-auto p-2">
                    <button
                        v-for="item in items"
                        :key="item.id"
                        type="button"
                        class="flex w-full gap-3 rounded-xl px-3 py-3 text-left transition-colors hover:bg-pale"
                    >
                        <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-tint text-base">
                            <i :class="item.icon || 'ti ti-bell'" aria-hidden="true" />
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-ink">{{ item.title }}</span>
                            <span class="mt-0.5 block text-xs font-medium text-ink/50">{{ item.body }}</span>
                            <span class="mt-1 block text-[11px] font-semibold text-ink/35">{{ item.time }}</span>
                        </span>
                    </button>
                </div>

                <div v-else class="px-6 py-10 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-pale text-ink/35">
                        <i class="ti ti-bell-off text-2xl" aria-hidden="true" />
                    </span>
                    <p class="mt-3 text-sm font-bold text-ink">You’re all caught up</p>
                    <p class="mt-1 text-xs font-medium leading-relaxed text-ink/45">
                        Reviews, credit updates, and plan reminders will show up here.
                    </p>
                </div>
            </div>
        </template>
    </Dropdown>
</template>

<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const unreadCount = computed(() => page.props.notifications?.unread_count ?? 0);
const items = computed(() => page.props.notifications?.items ?? []);
</script>
