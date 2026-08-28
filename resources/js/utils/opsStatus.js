export const dutyToneClass = (tone) => {
    const map = {
        support: 'bg-tint text-deep',
        moderator: 'bg-violet-50 text-violet-700',
        patrol: 'bg-amber-50 text-amber-800',
        verify: 'bg-teal-50 text-teal-800',
        hr: 'bg-emerald-50 text-emerald-800',
        discipline: 'bg-rose-50 text-rose-800',
        finance: 'bg-cyan-50 text-cyan-800',
        growth: 'bg-indigo-50 text-indigo-700',
        content: 'bg-fuchsia-50 text-fuchsia-700',
        default: 'bg-pale text-ink/55',
    };

    return map[tone] || map.default;
};

export const feedToneClass = (tone) => {
    const map = {
        high: 'bg-coral-tint text-coral-deep',
        medium: 'bg-amber-50 text-amber-800',
        low: 'bg-slate-100 text-slate-600',
        support: 'bg-tint text-deep',
        default: 'bg-pale text-ink/45',
    };

    return map[tone] || map.default;
};

export const formatBadgeCount = (count) => {
    const n = Number(count || 0);

    if (n <= 0) {
        return '';
    }

    return n > 9 ? '9+' : String(n);
};

export const padQueueCount = (count) => {
    const n = Math.max(0, Number(count || 0));

    return String(Math.min(99, n)).padStart(2, '0');
};

export const priorityPillClass = (priority) => {
    const map = {
        high: 'bg-rose-100 text-rose-800',
        medium: 'bg-amber-100 text-amber-800',
        low: 'bg-emerald-100 text-emerald-800',
    };

    return map[priority] || map.medium;
};

export const escalationStatusClass = (tone) => {
    const map = {
        resolved: 'text-emerald-700',
        review: 'text-amber-700',
        pending: 'text-amber-800',
    };

    return map[tone] || 'text-ink/55';
};
