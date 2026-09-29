export const queueStatusLabel = (status) => {
    const map = {
        new: 'New',
        open: 'Open',
        awaiting_customer: 'Waiting on customer',
        pending: 'Waiting on customer',
        resolved: 'Closed',
        abandoned: 'Abandoned',
        referred: 'Referred',
    };

    return map[status] || 'Open';
};

export const queueStatusClass = (status) => {
    const map = {
        new: 'bg-coral-tint text-coral-deep',
        open: 'bg-amber-50 text-amber-800',
        awaiting_customer: 'bg-tint text-deep',
        pending: 'bg-tint text-deep',
        resolved: 'bg-pale text-ink/40',
        abandoned: 'bg-ink/5 text-ink/45',
        referred: 'bg-violet-50 text-violet-800',
    };

    return map[status] || map.open;
};

export const customerStateDot = (state) => {
    const map = {
        connected: 'bg-emerald-500',
        waiting: 'bg-amber-400',
        next_agent: 'bg-amber-400',
        offline: 'bg-ink/25',
        resolved: 'bg-ink/25',
        idle: 'bg-ink/25',
    };

    return map[state] || map.waiting;
};
