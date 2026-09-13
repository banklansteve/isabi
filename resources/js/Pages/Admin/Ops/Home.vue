<template>
    <Head title="Home" />

    <AdminChrome title="Home" />

    <div
        v-if="restricted"
        class="flex flex-col items-center justify-center rounded-2xl bg-white px-6 py-20 text-center shadow-premium ring-1 ring-ink/[0.05]"
    >
        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-pale text-ink/30">
            <i class="ti ti-lock-access text-2xl" aria-hidden="true" />
        </span>
        <p class="mt-4 text-sm font-bold text-ink">No roles assigned yet</p>
        <p class="mt-1 max-w-md text-[13px] font-medium leading-relaxed text-ink/45">
            You can sign in, but a Super Admin still needs to give you a role before you can work in this console.
        </p>
    </div>

    <div v-else class="space-y-6 lg:space-y-8">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between sm:gap-6">
            <div class="min-w-0">
                <h1 class="font-editorial text-[1.85rem] font-semibold leading-[1.12] tracking-tight text-ink sm:text-[2.25rem]">
                    {{ greeting }}, {{ givenName }}.
                </h1>
                <p class="mt-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-ink/35">
                    {{ clockLabel }}
                    <span v-if="role_summary"> · {{ role_summary }}</span>
                </p>
            </div>
            <span
                v-if="duty?.badge"
                class="inline-flex w-fit shrink-0 items-center rounded-full bg-emerald-50 px-3 py-1.5 text-[12px] font-semibold text-emerald-800"
            >
                {{ duty.badge }}
            </span>
        </header>

        <OpsEscalateBanner :escalate="escalate" />

        <OpsPriorityPanel :groups="priorityGroups" :open-count="attentionOpenCount" />

        <!-- Launcher: reach any page you're allowed to work in -->
        <section v-if="launcher.length" class="space-y-5">
            <OpsSectionLabel label="Your workspace" />

            <div v-for="hub in launcher" :key="hub.key" class="space-y-2.5">
                <div class="flex items-center gap-2">
                    <i :class="hub.icon" class="text-[1.05rem] text-ink/40" aria-hidden="true" />
                    <h3 class="text-[13px] font-bold uppercase tracking-[0.12em] text-ink/45">{{ hub.label }}</h3>
                </div>
                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-4">
                    <Link
                        v-for="tile in hub.tiles"
                        :key="tile.key"
                        :href="tile.href"
                        class="group flex items-center gap-3 rounded-2xl bg-white p-3.5 shadow-premium ring-1 ring-ink/[0.05] transition-all duration-150 hover:-translate-y-0.5 hover:shadow-lg active:scale-[0.98]"
                    >
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-tint text-base-action">
                            <i :class="tile.icon" class="text-lg" aria-hidden="true" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[13px] font-bold text-ink">{{ tile.label }}</span>
                            <span class="mt-0.5 block truncate text-[11px] font-medium text-ink/40">
                                <template v-if="tile.count > 0">{{ tile.count }} {{ tile.hint }}</template>
                                <template v-else>Open</template>
                            </span>
                        </span>
                        <span
                            v-if="tile.count > 0"
                            class="shrink-0 rounded-full bg-base-action px-1.5 py-0.5 text-[10px] font-extrabold tabular-nums text-white"
                        >
                            {{ formatBadgeCount(tile.count) }}
                        </span>
                    </Link>
                </div>
            </div>
        </section>

        <OpsEscalationsCard v-if="escalations.length" :items="escalations" />
    </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import OpsPriorityPanel from '@/Components/Admin/OpsPriorityPanel.vue';
import OpsEscalateBanner from '@/Components/Admin/OpsEscalateBanner.vue';
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
    escalate: { type: Object, default: null },
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
