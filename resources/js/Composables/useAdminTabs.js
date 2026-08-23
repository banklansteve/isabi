import { computed, inject } from 'vue';

export function useAdminTabs(fallback = {}) {
    const query = inject('adminTabQuery', null);

    const current = computed(() => ({
        ...fallback,
        ...(query?.value || {}),
    }));

    return {
        query: current,
        tab: computed(() => String(current.value.tab || fallback.tab || '')),
        status: computed(() => String(current.value.status || fallback.status || '')),
        audience: computed(() => String(current.value.audience || fallback.audience || '')),
    };
}
