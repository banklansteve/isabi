<template>
    <Head title="Home" />

    <AdminChrome title="Home" />

    <div
        v-if="restricted"
        class="flex flex-col items-center justify-center rounded-[1.5rem] bg-white px-6 py-20 text-center shadow-premium ring-1 ring-ink/[0.05]"
    >
        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-pale text-ink/30">
            <i class="ti ti-lock-access text-2xl" aria-hidden="true" />
        </span>
        <p class="mt-4 text-sm font-bold text-ink">No roles assigned yet</p>
        <p class="mt-1 max-w-md text-[13px] font-medium leading-relaxed text-ink/45">
            You can sign in, but a Super Admin still needs to give you a role before you can work in this console.
        </p>
    </div>

    <div v-else class="space-y-5 pb-6 sm:space-y-7">
        <header class="relative overflow-hidden rounded-[1.5rem] bg-gradient-to-br from-[#0B1F3A] via-[#123B72] to-[#1A4FB5] px-5 py-6 text-white shadow-premium-ink sm:px-6 sm:py-7">
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(45%_55%_at_100%_100%,rgba(255,106,61,0.18),transparent_50%)]"
                aria-hidden="true"
            />
            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-white/50">
                        {{ clockLabel || 'Ops desk' }}
                    </p>
                    <h1 class="mt-2 font-editorial text-[1.85rem] font-semibold leading-[1.1] tracking-tight sm:text-[2.2rem]">
                        {{ greeting }}, {{ givenName }}.
                    </h1>
                    <p class="mt-2 max-w-lg text-sm font-medium leading-relaxed text-white/65">
                        <template v-if="attentionOpenCount > 0">
                            {{ attentionOpenCount }} {{ attentionOpenCount === 1 ? 'item needs' : 'items need' }} your attention — patrol cases first.
                        </template>
                        <template v-else>
                            You’re clear. New patrol flags and assigned work will land here.
                        </template>
                    </p>
                </div>
                <span
                    v-if="duty?.badge"
                    class="inline-flex w-fit shrink-0 items-center rounded-full bg-white/12 px-3 py-1.5 text-[12px] font-semibold text-white ring-1 ring-white/15"
                >
                    {{ duty.badge }}
                </span>
            </div>

            <div
                v-if="queueChips.length"
                class="relative mt-5 flex gap-2 overflow-x-auto pb-0.5 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
            >
                <Link
                    v-for="chip in queueChips"
                    :key="chip.key"
                    :href="chip.href"
                    class="tap-target inline-flex shrink-0 items-center gap-2 rounded-full bg-white/10 px-3.5 py-2 text-[12px] font-bold text-white ring-1 ring-white/15 transition-colors hover:bg-white/16"
                >
                    <i :class="chip.icon" class="text-sm opacity-80" aria-hidden="true" />
                    {{ chip.label }}
                    <span
                        v-if="chip.count > 0"
                        class="rounded-full bg-coral px-1.5 py-0.5 text-[10px] font-extrabold tabular-nums"
                    >
                        {{ formatBadgeCount(chip.count) }}
                    </span>
                </Link>
            </div>
        </header>

        <OpsPriorityPanel
            :groups="priorityGroups"
            :open-count="attentionOpenCount"
            heading="Work now"
        />

        <section v-if="launcher.length" class="space-y-4">
            <OpsSectionLabel label="Jump to a queue" />

            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-4">
                <Link
                    v-for="tile in flatTiles"
                    :key="tile.key"
                    :href="tile.href"
                    class="group flex min-h-[4.75rem] flex-col justify-between rounded-[1.25rem] bg-white p-3.5 shadow-premium ring-1 ring-ink/[0.05] transition-all duration-150 hover:-translate-y-0.5 hover:shadow-lg active:scale-[0.98]"
                >
                    <span class="flex items-center justify-between gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-tint text-base-action">
                            <i :class="tile.icon" class="text-base" aria-hidden="true" />
                        </span>
                        <span
                            v-if="tile.count > 0"
                            class="rounded-full bg-base-action px-1.5 py-0.5 text-[10px] font-extrabold tabular-nums text-white"
                        >
                            {{ formatBadgeCount(tile.count) }}
                        </span>
                    </span>
                    <span class="mt-3 min-w-0">
                        <span class="block truncate text-[13px] font-bold text-ink">{{ tile.label }}</span>
                        <span class="mt-0.5 block truncate text-[11px] font-medium text-ink/40">
                            <template v-if="tile.count > 0">{{ tile.count }} {{ tile.hint }}</template>
                            <template v-else>Open</template>
                        </span>
                    </span>
                </Link>
            </div>
        </section>

        <OpsEscalationsCard v-if="escalations.length" :items="escalations" />
    </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import OpsPriorityPanel from '@/Components/Admin/OpsPriorityPanel.vue';
import OpsEscalationsCard from '@/Components/Admin/OpsEscalationsCard.vue';
import OpsSectionLabel from '@/Components/Admin/OpsSectionLabel.vue';
import { navItemHref, visibleOpsHubs } from '@/Data/adminNav';
import { formatBadgeCount } from '@/utils/opsStatus';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    greeting: { type: String, default: '' },
    given_name: { type: String, default: '' },
    timezone: { type: String, default: 'Africa/Lagos' },
    roles: { type: Array, default: () => [] },
    role_summary: { type: String, default: '' },
    items: { type: Array, default: () => [] },
    priority_groups: { type: Array, default: () => [] },
    shortcuts: { type: Array, default: () => [] },
    duty: { type: Object, default: null },
    escalations: { type: Array, default: () => [] },
    open_count: { type: Number, default: 0 },
    unread_count: { type: Number, default: 0 },
    unread_items: { type: Array, default: () => [] },
    restricted: { type: Boolean, default: false },
});

const page = usePage();
const clockLabel = ref('');
let timer = null;

const opsInbox = computed(() => page.props.ops_inbox || {});
const abilities = computed(() => page.props.auth?.user?.abilities || []);

const priorityGroups = computed(() => {
    const groups = opsInbox.value.priority_groups;

    return Array.isArray(groups) ? groups : props.priority_groups;
});

const attentionOpenCount = computed(() =>
    typeof opsInbox.value.open_count === 'number' ? opsInbox.value.open_count : props.open_count,
);

const shortcuts = computed(() => {
    const live = opsInbox.value.shortcuts;

    return Array.isArray(live) && live.length ? live : props.shortcuts;
});

const hrefPath = (href) => {
    try {
        return new URL(href, window.location.origin).pathname.replace(/\/+$/, '') || '/';
    } catch {
        return String(href || '').split('?')[0].replace(/\/+$/, '') || '';
    }
};

const shortcutMap = computed(() => {
    const map = new Map();
    for (const shortcut of shortcuts.value) {
        map.set(hrefPath(shortcut.href), { count: Number(shortcut.count || 0), hint: shortcut.hint || 'open' });
    }
    return map;
});

const launcher = computed(() =>
    visibleOpsHubs(false, abilities.value)
        .filter((hub) => (hub.pages || []).length > 0)
        .map((hub) => ({
            key: hub.key,
            label: hub.label,
            icon: hub.icon,
            tiles: hub.pages.map((item) => {
                const href = navItemHref(item, false, abilities.value);
                const meta = shortcutMap.value.get(hrefPath(href)) || { count: 0, hint: 'open' };

                return {
                    key: item.key,
                    label: item.shortLabel || item.label,
                    icon: item.icon,
                    href,
                    count: meta.count,
                    hint: meta.hint,
                };
            }),
        })),
);

const flatTiles = computed(() =>
    launcher.value
        .flatMap((hub) => hub.tiles)
        .sort((a, b) => (b.count || 0) - (a.count || 0)),
);

const queueChips = computed(() => {
    const preferred = [
        'patrol_jobs',
        'patrol_reviews',
        'support',
        'assigned',
        'onboarding',
        'reengagement',
        'dormant',
        'billing',
        'approvals',
    ];

    return shortcuts.value
        .filter((item) => preferred.includes(item.key) || Number(item.count || 0) > 0)
        .slice(0, 8)
        .map((item) => ({
            key: item.key,
            label: item.label,
            icon: item.icon,
            href: item.href,
            count: Number(item.count || 0),
        }));
});

const givenName = computed(() => {
    if (props.given_name) {
        return props.given_name;
    }

    const user = page.props.auth?.user;

    return user?.first_name || String(user?.name || 'there').split(' ')[0];
});

const tick = () => {
    try {
        const parts = Object.fromEntries(
            new Intl.DateTimeFormat('en-GB', {
                weekday: 'short',
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
                timeZone: props.timezone || 'Africa/Lagos',
            }).formatToParts(new Date()).map((part) => [part.type, part.value]),
        );

        clockLabel.value = `${parts.weekday} ${parts.day} ${parts.month} ${parts.year} · ${parts.hour}:${parts.minute}`.toUpperCase();
    } catch {
        clockLabel.value = '';
    }
};

onMounted(() => {
    tick();
    timer = window.setInterval(tick, 30000);
});

onUnmounted(() => {
    if (timer) {
        window.clearInterval(timer);
    }
});
</script>
