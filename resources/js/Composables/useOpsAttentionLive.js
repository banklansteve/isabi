import { router, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

let refreshTimer = null;

const isOpsConsole = (page) => {
    const user = page.props.auth?.user;

    return Boolean(user?.is_staff && !user?.is_super_admin && !user?.restricted);
};

export function useOpsAttentionLive() {
    const page = usePage();

    const refresh = ({ skipAsapRoute = true } = {}) => {
        if (!isOpsConsole(page)) {
            return;
        }

        if (skipAsapRoute) {
            try {
                const current = route().current() || '';

                if (String(current).startsWith('admin.asap')) {
                    return;
                }
            } catch {
                // Ignore route lookup failures.
            }
        }

        window.clearTimeout(refreshTimer);
        refreshTimer = window.setTimeout(() => {
            router.reload({
                only: ['ops_inbox', 'asap_unread'],
                preserveScroll: true,
                preserveState: true,
            });
        }, 350);
    };

    const onStaffChat = () => refresh();
    const onSupportInbox = () => refresh({ skipAsapRoute: false });
    const onAttentionRefresh = () => refresh({ skipAsapRoute: false });

    onMounted(() => {
        window.addEventListener('kraftrack:staff-chat', onStaffChat);
        window.addEventListener('kraftrack:support-inbox', onSupportInbox);
        window.addEventListener('kraftrack:ops-attention-refresh', onAttentionRefresh);
    });

    onUnmounted(() => {
        window.removeEventListener('kraftrack:staff-chat', onStaffChat);
        window.removeEventListener('kraftrack:support-inbox', onSupportInbox);
        window.removeEventListener('kraftrack:ops-attention-refresh', onAttentionRefresh);
        window.clearTimeout(refreshTimer);
    });
}

export function refreshOpsAttention(options = {}) {
    window.dispatchEvent(new CustomEvent('kraftrack:ops-attention-refresh', { detail: options }));
}
