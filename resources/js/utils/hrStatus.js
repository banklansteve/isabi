// Shared status-colour conventions for the HR / People module.
// These reuse the admin panel's existing badge language so the module
// never invents a new colour vocabulary.

const PILL = 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold';

export const employmentStatusMeta = (status) => {
    const map = {
        active: { label: 'Active', class: 'bg-emerald-50 text-emerald-700', dot: 'bg-emerald-500' },
        on_leave: { label: 'On leave', class: 'bg-amber-50 text-amber-800', dot: 'bg-amber-500' },
        exited: { label: 'Exited', class: 'bg-slate-100 text-slate-500', dot: 'bg-slate-400' },
        unmanaged: { label: 'No HR profile', class: 'bg-pale text-ink/45', dot: 'bg-ink/25' },
    };
    return map[status] || map.unmanaged;
};

export const leaveStatusMeta = (status) => {
    const map = {
        pending: { label: 'Pending', class: 'bg-amber-50 text-amber-800' },
        approved: { label: 'Approved', class: 'bg-emerald-50 text-emerald-700' },
        rejected: { label: 'Rejected', class: 'bg-coral-tint text-coral-deep' },
        cancelled: { label: 'Cancelled', class: 'bg-slate-100 text-slate-500' },
    };
    return map[status] || map.pending;
};

export const payslipStatusMeta = (status) => {
    const map = {
        draft: { label: 'Draft', class: 'bg-slate-100 text-slate-500' },
        issued: { label: 'Issued', class: 'bg-tint text-deep' },
        paid: { label: 'Paid', class: 'bg-emerald-50 text-emerald-700' },
    };
    return map[status] || map.draft;
};

export const documentExpiryMeta = (state) => {
    const map = {
        expired: { label: 'Expired', class: 'bg-coral-tint text-coral-deep' },
        expiring: { label: 'Expiring soon', class: 'bg-amber-50 text-amber-800' },
        valid: { label: 'Valid', class: 'bg-emerald-50 text-emerald-700' },
    };
    return state ? map[state] || null : null;
};

export const disciplineStatusMeta = (status) => {
    const map = {
        reported: { label: 'Reported', class: 'bg-slate-100 text-slate-600' },
        investigating: { label: 'Under investigation', class: 'bg-tint text-deep' },
        decision_pending: { label: 'Decision pending', class: 'bg-amber-50 text-amber-800' },
        action_issued: { label: 'Action issued', class: 'bg-base/10 text-base-action' },
        appealed: { label: 'Appealed', class: 'bg-violet-50 text-violet-700' },
        resolved: { label: 'Resolved', class: 'bg-emerald-50 text-emerald-700' },
        closed: { label: 'Closed', class: 'bg-slate-100 text-slate-500' },
        open: { label: 'Open', class: 'bg-slate-100 text-slate-600' },
        monitoring: { label: 'Monitoring', class: 'bg-amber-50 text-amber-800' },
    };
    return map[status] || { label: status || 'Reported', class: 'bg-slate-100 text-slate-600' };
};

export const disciplineSeverityMeta = (severity) => {
    const map = {
        minor: { label: 'Minor', class: 'bg-slate-100 text-slate-600' },
        moderate: { label: 'Moderate', class: 'bg-amber-50 text-amber-800' },
        serious: { label: 'Serious', class: 'bg-coral-tint text-coral-deep' },
    };
    return map[severity] || map.moderate;
};

export const disciplineTypeMeta = (type) => {
    const map = {
        verbal_warning: { label: 'Verbal warning' },
        written_warning: { label: 'Written warning' },
        final_warning: { label: 'Final written warning' },
        final_written_warning: { label: 'Final written warning' },
        suspension: { label: 'Suspension' },
        termination: { label: 'Termination of employment' },
        no_action: { label: 'No action taken' },
        improvement_plan: { label: 'Improvement plan' },
        other: { label: 'Other' },
    };
    return map[type] || { label: type || 'Notice' };
};

export const ratingMeta = (rating) => {
    const map = {
        exceeding: { label: 'Exceeding', class: 'bg-emerald-50 text-emerald-700' },
        meeting: { label: 'Meeting', class: 'bg-tint text-deep' },
        needs_improvement: { label: 'Needs improvement', class: 'bg-amber-50 text-amber-800' },
    };
    return rating ? map[rating] || null : null;
};

// Calendar / leave-type chip colours.
export const leaveColorClasses = (token) => {
    const map = {
        base: 'bg-base/12 text-deep ring-1 ring-base/20',
        coral: 'bg-coral-tint text-coral-deep ring-1 ring-coral/20',
        emerald: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
        amber: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200',
        violet: 'bg-violet-50 text-violet-700 ring-1 ring-violet-200',
        slate: 'bg-slate-100 text-slate-600 ring-1 ring-slate-200',
    };
    return map[token] || map.base;
};

export const leaveDotClass = (token) => {
    const map = {
        base: 'bg-base',
        coral: 'bg-coral',
        emerald: 'bg-emerald-500',
        amber: 'bg-amber-500',
        violet: 'bg-violet-500',
        slate: 'bg-slate-400',
    };
    return map[token] || map.base;
};

export const pillBase = PILL;

export const formatMoney = (amount, currency = 'NGN') => {
    const value = Number(amount || 0).toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });
    return `${currency} ${value}`;
};
