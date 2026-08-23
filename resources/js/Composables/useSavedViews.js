import { computed, ref, watch } from 'vue';

export function useSavedViews(storageKey, current) {
    const views = ref(load());
    const name = ref('');

    watch(views, (value) => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(value));
        } catch {
            // ignore quota / private mode
        }
    }, { deep: true });

    const snapshot = () => {
        const value = typeof current === 'function' ? current() : current;
        return JSON.parse(JSON.stringify(value));
    };

    const save = (label) => {
        const trimmed = String(label || name.value).trim();
        if (!trimmed) {
            return;
        }

        views.value = [
            ...views.value.filter((view) => view.name !== trimmed),
            { id: `${Date.now()}`, name: trimmed, filters: snapshot() },
        ];
        name.value = '';
    };

    const remove = (id) => {
        views.value = views.value.filter((view) => view.id !== id);
    };

    const isActive = computed(() => {
        const now = JSON.stringify(snapshot());
        return views.value.find((view) => JSON.stringify(view.filters) === now)?.id || '';
    });

    function load() {
        try {
            const raw = localStorage.getItem(storageKey);
            const parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed : [];
        } catch {
            return [];
        }
    }

    return { views, name, save, remove, isActive, snapshot };
}
