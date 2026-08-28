export const RANGE_PRESETS = [
    { id: 'today', label: 'Today' },
    { id: 'yesterday', label: 'Yesterday' },
    { id: 'this_week', label: 'This week' },
    { id: 'last_3', label: 'Last 3 days' },
    { id: 'last_7', label: 'Last 7 days' },
    { id: 'last_30', label: 'Last 30 days' },
    { id: 'this_month', label: 'This month' },
    { id: 'last_month', label: 'Last month' },
    { id: 'custom', label: 'Custom' },
    { id: 'all', label: 'All time' },
];

const startOfDay = (date) => {
    const next = new Date(date);
    next.setHours(0, 0, 0, 0);
    return next;
};

const endOfDay = (date) => {
    const next = new Date(date);
    next.setHours(23, 59, 59, 999);
    return next;
};

export function rangeBounds(id, customFrom = '', customTo = '') {
    const now = new Date();
    const today = startOfDay(now);

    if (id === 'all') {
        return { from: null, to: null };
    }

    if (id === 'today') {
        return { from: startOfDay(now), to: endOfDay(now) };
    }

    if (id === 'yesterday') {
        const day = new Date(today);
        day.setDate(day.getDate() - 1);
        return { from: startOfDay(day), to: endOfDay(day) };
    }

    if (id === 'this_week') {
        const day = now.getDay();
        const mondayOffset = day === 0 ? -6 : 1 - day;
        const from = new Date(today);
        from.setDate(from.getDate() + mondayOffset);
        return { from: startOfDay(from), to: endOfDay(now) };
    }

    if (id === 'last_3' || id === 'last_7' || id === 'last_30') {
        const days = id === 'last_3' ? 3 : id === 'last_7' ? 7 : 30;
        const from = new Date(today);
        from.setDate(from.getDate() - (days - 1));
        return { from: startOfDay(from), to: endOfDay(now) };
    }

    if (id === 'this_month') {
        return { from: startOfDay(new Date(now.getFullYear(), now.getMonth(), 1)), to: endOfDay(now) };
    }

    if (id === 'last_month') {
        const from = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const to = new Date(now.getFullYear(), now.getMonth(), 0);
        return { from: startOfDay(from), to: endOfDay(to) };
    }

    if (id === 'custom' && customFrom && customTo) {
        return { from: startOfDay(customFrom), to: endOfDay(customTo) };
    }

    return { from: null, to: null };
}

export function inRange(iso, bounds) {
    if (!bounds?.from || !iso) {
        return true;
    }

    const time = new Date(iso).getTime();
    if (Number.isNaN(time)) {
        return true;
    }

    if (time < bounds.from.getTime()) {
        return false;
    }

    return !bounds.to || time <= bounds.to.getTime();
}

function bucketOverlaps(startIso, nextIso, bounds) {
    if (!bounds?.from) {
        return true;
    }

    const start = new Date(startIso).getTime();
    if (Number.isNaN(start)) {
        return true;
    }

    const next = nextIso ? new Date(nextIso).getTime() : NaN;
    const end = Number.isNaN(next) ? (bounds.to?.getTime() ?? Date.now()) : next;

    if (end <= bounds.from.getTime()) {
        return false;
    }

    return !bounds.to || start <= bounds.to.getTime();
}

export function filterSeries(series, bounds) {
    if (!bounds?.from) {
        return series;
    }

    const rows = series || [];

    return rows.filter((point, index) =>
        bucketOverlaps(point.date || point.at, rows[index + 1]?.date || rows[index + 1]?.at, bounds),
    );
}

export function sliceStacked(source, bounds) {
    const labels = source?.labels || [];
    const dates = source?.dates || [];
    const layers = source?.layers || [];

    if (!bounds?.from || !dates.length) {
        return source || { labels: [], layers: [] };
    }

    const indexes = dates
        .map((_, index) => index)
        .filter((index) => bucketOverlaps(dates[index], dates[index + 1], bounds));

    return {
        labels: indexes.map((index) => labels[index]),
        dates: indexes.map((index) => dates[index]),
        layers: layers.map((layer) => ({
            ...layer,
            values: indexes.map((index) => layer.values?.[index] ?? 0),
        })),
    };
}

export function formatNaira(value) {
    const amount = Number(value) || 0;
    if (Math.abs(amount) >= 1_000_000) {
        return `₦${(amount / 1_000_000).toFixed(1).replace(/\.0$/, '')}M`;
    }
    return `₦${amount.toLocaleString()}`;
}

export function formatCompact(value) {
    const amount = Number(value) || 0;
    if (Math.abs(amount) >= 1_000_000) {
        return `${(amount / 1_000_000).toFixed(1).replace(/\.0$/, '')}M`;
    }
    if (Math.abs(amount) >= 10_000) {
        return `${(amount / 1_000).toFixed(1).replace(/\.0$/, '')}k`;
    }
    return amount.toLocaleString();
}

export function showAxisLabel(index, total) {
    if (total <= 8) {
        return true;
    }
    const step = Math.ceil(total / 6);
    return index === 0 || index === total - 1 || index % step === 0;
}

export function toast(detail) {
    window.dispatchEvent(new CustomEvent('isabi:toast', { detail }));
}

export function parseQuery(url) {
    try {
        return Object.fromEntries(new URL(url, 'http://local').searchParams);
    } catch {
        return {};
    }
}
