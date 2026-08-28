import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

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

export function useOpsAttention() {
    const page = usePage();

    const markRead = async (item) => {
        if (!item?.key) {
            return;
        }

        dropInboxItem(page.props.ops_inbox, item.key);
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
        }

        try {
            await axios.post(route('admin.attention.read-all'));
        } catch {
            // The local badge is already cleared.
        }
    };

    return { markRead, markAll };
}
