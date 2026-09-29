import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const SHORTCUT_BY_GROUP = {
    support: 'support',
    patrol_jobs: 'patrol_jobs',
    patrol_reviews: 'patrol_reviews',
    onboarding: 'onboarding',
    reengagement: 'reengagement',
    dormant: 'dormant',
    flagged_jobs: 'jobs',
    flagged_reviews: 'reviews',
};

const dropInboxItem = (inbox, key) => {
    if (!inbox || !key) {
        return;
    }

    const items = Array.isArray(inbox.items) ? inbox.items : [];
    const wasUnread = items.some((entry) => entry.key === key && entry.unread !== false);

    inbox.items = items.filter((entry) => entry.key !== key);

    if (wasUnread) {
        inbox.unread_count = Math.max(0, Number(inbox.unread_count || 0) - 1);
    }
};

const markGroupItemRead = (groups, key) => {
    if (!Array.isArray(groups) || !key) {
        return groups;
    }

    return groups.map((group) => ({
        ...group,
        items: (group.items || []).map((entry) =>
            entry.key === key ? { ...entry, unread: false } : entry,
        ),
    }));
};

const markAttentionItems = (items, key) => {
    if (!Array.isArray(items) || !key) {
        return;
    }

    const hit = items.find((entry) => entry.key === key);

    if (hit?.unread) {
        hit.unread = false;
    }
};

const markPageItems = (page, key) => {
    const items = page.props.items;

    if (!Array.isArray(items) || !key) {
        return;
    }

    const hit = items.find((entry) => entry.key === key);

    if (!hit?.unread) {
        return;
    }

    hit.unread = false;

    if (typeof page.props.unread_count === 'number') {
        page.props.unread_count = Math.max(0, page.props.unread_count - 1);
    }
};

const decrementShortcut = (inbox, item) => {
    if (!inbox || !item?.unread) {
        return;
    }

    const shortcutKey = SHORTCUT_BY_GROUP[item.group];

    if (!shortcutKey || !Array.isArray(inbox.shortcuts)) {
        return;
    }

    const delta = Math.max(1, Number(item.count || 1));

    inbox.shortcuts = inbox.shortcuts.map((shortcut) => {
        if (shortcut.key !== shortcutKey) {
            return shortcut;
        }

        return {
            ...shortcut,
            count: Math.max(0, Number(shortcut.count || 0) - delta),
        };
    });
};

export function useOpsAttention() {
    const page = usePage();

    const markRead = async (item) => {
        if (!item?.key) {
            return;
        }

        decrementShortcut(page.props.ops_inbox, item);
        dropInboxItem(page.props.ops_inbox, item.key);

        if (page.props.ops_inbox) {
            page.props.ops_inbox.priority_groups = markGroupItemRead(
                page.props.ops_inbox.priority_groups,
                item.key,
            );
            markAttentionItems(page.props.ops_inbox.attention_items, item.key);
        }

        markPageItems(page, item.key);

        if (!item.unread || !item.signature) {
            return;
        }

        try {
            await axios.post(route('admin.attention.read'), {
                key: item.key,
                signature: item.signature,
            });
        } catch {
            // Keep the optimistic unread drop even if the request is slow or offline.
        }
    };

    const markAll = async () => {
        const inbox = page.props.ops_inbox;

        if (inbox) {
            inbox.items = [];
            inbox.unread_count = 0;

            if (Array.isArray(inbox.shortcuts)) {
                inbox.shortcuts = inbox.shortcuts.map((shortcut) => ({
                    ...shortcut,
                    count: Object.values(SHORTCUT_BY_GROUP).includes(shortcut.key)
                        ? 0
                        : shortcut.count,
                }));
            }
        }

        try {
            await axios.post(route('admin.attention.read-all'));
        } catch {
            // The local badge is already cleared.
        }
    };

    return { markRead, markAll };
}
