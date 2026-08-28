<template>
    <div v-if="linkedRoles.length" class="hidden items-center lg:flex">
        <nav
            v-if="linkedRoles.length <= 3"
            class="flex rounded-full bg-pale p-1 ring-1 ring-ink/[0.06]"
            aria-label="Roles"
        >
            <Link
                v-for="role in linkedRoles"
                :key="role.slug"
                :href="role.href"
                prefetch
                cache-for="5m"
                :show-progress="false"
                class="shrink-0 rounded-full px-3.5 py-1.5 text-[13px] font-semibold transition-all duration-150 active:scale-[0.97]"
                :class="isRoleActive(role) ? 'bg-white text-deep shadow-sm' : 'text-ink/50 hover:text-ink'"
            >
                {{ role.short }}
            </Link>
        </nav>

        <Dropdown v-else align="right" width="72" content-classes="p-1.5">
            <template #trigger="{ open }">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full bg-pale px-3.5 py-2 text-[13px] font-semibold text-ink/70 ring-1 ring-ink/[0.06] transition-colors hover:bg-white hover:text-ink"
                    :aria-expanded="open"
                >
                    {{ activeRole?.short || roleSummary }}
                    <i class="ti ti-chevron-down text-sm text-ink/35" aria-hidden="true" />
                </button>
            </template>
            <template #content>
                <Link
                    v-for="role in linkedRoles"
                    :key="role.slug"
                    :href="role.href"
                    class="flex items-start gap-3 rounded-xl px-3 py-2.5 hover:bg-pale"
                    :class="isRoleActive(role) ? 'bg-tint/70' : ''"
                >
                    <span
                        class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm"
                        :class="dutyToneClass(role.tone)"
                    >
                        <i :class="role.icon || 'ti ti-user'" aria-hidden="true" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-[13px] font-bold text-ink">{{ role.name }}</span>
                        <span v-if="role.description" class="mt-0.5 block text-[12px] font-medium leading-snug text-ink/45">
                            {{ role.description }}
                        </span>
                    </span>
                </Link>
            </template>
        </Dropdown>
    </div>
</template>

<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import { dutyToneClass } from '@/utils/opsStatus';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    roles: { type: Array, default: () => [] },
    roleSummary: { type: String, default: 'Roles' },
});

const page = usePage();
const linkedRoles = computed(() => props.roles.filter((role) => role.href));

const currentPath = computed(() => {
    try {
        return new URL(page.url, window.location.origin).pathname.replace(/\/+$/, '') || '/';
    } catch {
        return String(page.url || '').split('?')[0] || '';
    }
});

const pathOf = (href) => {
    try {
        return new URL(href, window.location.origin).pathname.replace(/\/+$/, '') || '/';
    } catch {
        return '';
    }
};

const activeRole = computed(() => {
    const matches = linkedRoles.value
        .map((role) => ({ role, path: pathOf(role.href) }))
        .filter(({ path }) => path && (currentPath.value === path || currentPath.value.startsWith(`${path}/`)))
        .sort((a, b) => b.path.length - a.path.length);

    return matches[0]?.role ?? null;
});

const isRoleActive = (role) => activeRole.value?.slug === role.slug;
</script>
