const PILL = 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold';

export const patrolSeverityMeta = (severity) => {
    const map = {
        low: { label: 'Low', class: 'bg-slate-100 text-slate-600' },
        medium: { label: 'Medium', class: 'bg-amber-50 text-amber-800' },
        high: { label: 'High', class: 'bg-coral-tint text-coral-deep' },
    };
    return map[severity] || map.medium;
};

export const patrolStatusMeta = (status) => {
    const map = {
        new: { label: 'New', class: 'bg-tint text-deep' },
        in_review: { label: 'In review', class: 'bg-amber-50 text-amber-800' },
        pending_approval: { label: 'Pending Super Admin approval', class: 'bg-violet-50 text-violet-700' },
        resolved_dismissed: { label: 'Resolved — dismissed', class: 'bg-emerald-50 text-emerald-700' },
        resolved_actioned: { label: 'Resolved — actioned', class: 'bg-slate-100 text-slate-500' },
    };
    return map[status] || { label: status || 'New', class: 'bg-slate-100 text-slate-600' };
};

export const patrolVisibilityMeta = (visibility) => {
    const map = {
        visible: { label: 'Visible', class: 'bg-emerald-50 text-emerald-700' },
        hidden: { label: 'Hidden', class: 'bg-amber-50 text-amber-800' },
        removed: { label: 'Removed', class: 'bg-slate-100 text-slate-500' },
    };
    return map[visibility] || map.visible;
};

export const patrolPill = PILL;
