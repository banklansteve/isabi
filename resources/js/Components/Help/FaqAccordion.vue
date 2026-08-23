<template>
    <div class="space-y-8">
        <section
            v-for="group in groups"
            :id="group.id"
            :key="group.id"
            class="scroll-mt-28"
        >
            <div class="mb-4 flex items-center gap-3">
                <span
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-tint text-deep"
                >
                    <i :class="group.icon" aria-hidden="true" />
                </span>
                <h2 class="font-editorial text-xl font-semibold tracking-tight text-ink sm:text-2xl">
                    {{ group.title }}
                </h2>
            </div>

            <div class="overflow-hidden rounded-[1.35rem] bg-white shadow-premium ring-1 ring-ink/[0.06]">
                <div
                    v-for="(item, index) in group.items"
                    :id="item.id"
                    :key="item.id"
                    class="scroll-mt-28"
                    :class="index > 0 ? 'border-t border-ink/[0.06]' : ''"
                >
                    <h3>
                        <button
                            type="button"
                            class="tap-target flex w-full items-center justify-between gap-4 px-5 py-4 text-left sm:px-6 sm:py-5"
                            :aria-expanded="openId === item.id"
                            :aria-controls="`faq-panel-${item.id}`"
                            @click="toggle(item.id)"
                        >
                            <span class="text-sm font-bold tracking-tight text-ink sm:text-[0.95rem]">
                                {{ item.question }}
                            </span>
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-pale text-ink/45 transition-transform duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]"
                                :class="openId === item.id ? 'rotate-180 bg-tint text-deep' : ''"
                                aria-hidden="true"
                            >
                                <i class="ti ti-chevron-down text-base" />
                            </span>
                        </button>
                    </h3>

                    <div
                        :id="`faq-panel-${item.id}`"
                        class="grid transition-[grid-template-rows] duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]"
                        :class="openId === item.id ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
                        role="region"
                        :aria-labelledby="item.id"
                    >
                        <div class="min-h-0 overflow-hidden">
                            <div class="px-5 pb-5 sm:px-6 sm:pb-6">
                                <p class="max-w-2xl text-sm font-medium leading-relaxed text-ink/55">
                                    {{ item.answer }}
                                </p>
                                <div
                                    v-if="visibleLinks(item).length"
                                    class="mt-4 flex flex-wrap gap-2"
                                >
                                    <component
                                        :is="linkIsExternal(link) ? 'a' : Link"
                                        v-for="link in visibleLinks(item)"
                                        :key="link.label"
                                        v-bind="linkAttrs(link)"
                                        class="tap-target inline-flex items-center gap-1.5 rounded-xl bg-pale px-3 py-2 text-xs font-bold text-deep transition-colors hover:bg-tint"
                                    >
                                        {{ link.label }}
                                        <i class="ti ti-arrow-up-right text-sm" aria-hidden="true" />
                                    </component>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    groups: {
        type: Array,
        required: true,
    },
    /** Open this item on mount / when hash changes */
    initialId: {
        type: String,
        default: null,
    },
});

const page = usePage();
const openId = ref(null);
const isAuthenticated = () => !!page.props.auth?.user;

const toggle = (id) => {
    openId.value = openId.value === id ? null : id;
    if (openId.value && typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.hash = openId.value;
        window.history.replaceState({}, '', url);
    }
};

const visibleLinks = (item) => {
    const auth = isAuthenticated();
    return (item.links ?? []).filter((link) => {
        if (link.authOnly && !auth) return false;
        if (link.guestOnly && auth) return false;
        return true;
    });
};

const resolveHref = (link) => {
    if (link.href) return link.href;
    if (link.route) {
        const base = route(link.route);
        return link.hash ? `${base}#${link.hash}` : base;
    }
    if (link.hash) return `#${link.hash}`;
    return '#';
};

const linkIsExternal = (link) => {
    const href = resolveHref(link);
    return href.startsWith('mailto:') || href.startsWith('http');
};

const linkAttrs = (link) => {
    const href = resolveHref(link);
    if (linkIsExternal(link)) {
        return {
            href,
            ...(href.startsWith('http')
                ? { target: '_blank', rel: 'noopener noreferrer' }
                : {}),
        };
    }
    return { href };
};

const openFromHash = () => {
    if (typeof window === 'undefined') return;
    const hash = (props.initialId || window.location.hash.replace('#', '')).trim();
    if (!hash) return;
    const exists = props.groups.some((g) => g.items.some((i) => i.id === hash));
    if (!exists) return;
    openId.value = hash;
    requestAnimationFrame(() => {
        document.getElementById(hash)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
};

onMounted(() => {
    openFromHash();
    window.addEventListener('hashchange', openFromHash);
});

onUnmounted(() => {
    window.removeEventListener('hashchange', openFromHash);
});

watch(
    () => props.initialId,
    () => openFromHash(),
);
</script>
