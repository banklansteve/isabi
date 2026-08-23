import { computed, ref, watch } from 'vue';

export function useClientList(source, options = {}) {
    const perPage = options.perPage || 20;
    const q = ref('');
    const sort = ref(options.sort || 'date_desc');
    const page = ref(1);

    const items = computed(() => (typeof source === 'function' ? source() : source.value || source || []));

    const filtered = computed(() => {
        const needle = q.value.trim().toLowerCase();
        let rows = items.value;

        if (needle) {
            const fields = options.searchFields || [];
            rows = rows.filter((row) =>
                fields.some((field) => String(read(row, field) || '').toLowerCase().includes(needle)),
            );
        }

        if (typeof options.filter === 'function') {
            rows = rows.filter((row) => options.filter(row));
        }

        const sorted = [...rows];
        const [key, dir] = String(sort.value).split('_');
        const factor = dir === 'asc' ? 1 : -1;

        sorted.sort((a, b) => {
            const left = read(a, options.sortMap?.[key] || key);
            const right = read(b, options.sortMap?.[key] || key);

            if (left == null && right == null) return 0;
            if (left == null) return 1;
            if (right == null) return -1;

            if (typeof left === 'number' && typeof right === 'number') {
                return (left - right) * factor;
            }

            return String(left).localeCompare(String(right), undefined, { numeric: true }) * factor;
        });

        return sorted;
    });

    const pageCount = computed(() => Math.max(1, Math.ceil(filtered.value.length / perPage)));

    const pageItems = computed(() => {
        const start = (page.value - 1) * perPage;
        return filtered.value.slice(start, start + perPage);
    });

    watch([q, sort, items], () => {
        page.value = 1;
    });

    watch(pageCount, (count) => {
        if (page.value > count) {
            page.value = count;
        }
    });

    return {
        q,
        sort,
        page,
        perPage,
        filtered,
        pageItems,
        pageCount,
        total: computed(() => filtered.value.length),
    };
}

function read(row, path) {
    if (!path) {
        return row;
    }

    return String(path)
        .split('.')
        .reduce((value, key) => value?.[key], row);
}
