<template>
    <Head title="Assigned to me" />

    <AdminChrome title="Assigned to me" />

    <div class="mx-auto max-w-4xl space-y-5">
        <OpsAssignedTabs v-if="!isSuper" />

        <header class="space-y-1">
            <h1 class="font-editorial text-[1.65rem] font-semibold tracking-tight text-ink sm:text-[1.85rem]">
                Assigned to me
            </h1>
            <p class="text-[13px] font-medium text-ink/45">
                Cases colleagues handed you — moderation first.
            </p>
        </header>

        <nav
            class="flex gap-1 overflow-x-auto rounded-xl bg-white p-1 shadow-premium ring-1 ring-ink/[0.05]"
            aria-label="Assigned queues"
        >
            <Link
                v-for="tab in tabs"
                :key="tab.key"
                :href="route('admin.assigned.index', { queue: tab.key })"
                class="shrink-0 rounded-lg px-3 py-2.5 text-center text-[13px] font-semibold transition-colors"
                :class="queue === tab.key ? 'bg-base-action text-white shadow-sm' : 'text-ink/45 hover:bg-pale hover:text-ink'"
            >
                {{ tab.label }}
                <span
                    v-if="counts[tab.countKey] > 0"
                    class="ms-1"
                    :class="queue === tab.key ? 'text-white/80' : 'text-coral-deep'"
                >
                    {{ counts[tab.countKey] }}
                </span>
            </Link>
        </nav>

        <div v-if="items.length" class="space-y-2">
            <Link
                v-for="item in items"
                :key="item.id"
                :href="item.href"
                class="block rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] transition-colors hover:bg-pale/60 sm:p-5"
            >
                <div class="flex items-start gap-3">
                    <span
                        class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                        :class="item.queue === 'moderation' ? 'bg-coral/10 text-coral-deep' : 'bg-pale text-ink/45'"
                    >
                        <i :class="item.icon || 'ti ti-transfer'" class="text-lg" aria-hidden="true" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-[15px] font-bold tracking-tight text-ink">{{ item.title }}</p>
                            <span class="rounded-full bg-pale px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-ink/50">
                                {{ item.queue_label }}
                            </span>
                        </div>
                        <p class="mt-1 text-[13px] font-medium leading-relaxed text-ink/55">{{ item.subtitle }}</p>
                        <p v-if="item.note" class="mt-2 text-[13px] font-medium text-ink/70">{{ item.note }}</p>
                        <p class="mt-2 text-[12px] font-semibold text-ink/35">
                            {{ item.age || item.referred_at }}
                            <span v-if="item.referrer?.name"> · From {{ item.referrer.name }}</span>
                        </p>
                    </div>
                    <i class="ti ti-chevron-right mt-2 text-ink/25" aria-hidden="true" />
                </div>
            </Link>
        </div>

        <AdminEmpty
            v-else
            title="Nothing assigned right now"
            description="When a colleague refers a case to you, it shows up here."
            icon="ti ti-inbox"
        />
    </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import OpsAssignedTabs from '@/Components/Admin/OpsAssignedTabs.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const isSuper = computed(() => !!page.props.auth?.user?.is_super_admin);

const props = defineProps({
    queue: { type: String, default: 'moderation' },
    counts: {
        type: Object,
        default: () => ({ all: 0, moderation: 0, support: 0, patrol: 0, jobs: 0 }),
    },
    items: { type: Array, default: () => [] },
    staff: { type: Array, default: () => [] },
});

const tabs = computed(() => [
    { key: 'moderation', label: 'Moderation', countKey: 'moderation' },
    { key: 'all', label: 'All', countKey: 'all' },
    { key: 'support', label: 'Support', countKey: 'support' },
    { key: 'patrol', label: 'Patrol', countKey: 'patrol' },
    { key: 'jobs', label: 'Jobs', countKey: 'jobs' },
]);
</script>
