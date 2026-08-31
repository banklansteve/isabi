import { echoClient } from '@/echo';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, onUnmounted, watch } from 'vue';

export function useRealtime() {
    const page = usePage();
    let pingTimer = null;
    let subscribedUid = null;

    const uid = () => page.props.auth?.user?.uid || null;
    const isStaff = () => Boolean(page.props.auth?.user?.is_staff);

    const client = () => echoClient(page.props.reverb);

    const ping = (interaction = false) => {
        if (!uid() || typeof route !== 'function') {
            return;
        }

        axios
            .post(
                route('realtime.ping'),
                { interaction: interaction ? 1 : 0 },
                { headers: { Accept: 'application/json' } },
            )
            .catch(() => {});
    };

    let lastInteractionAt = 0;
    const onInteract = () => {
        if (!isStaff()) {
            return;
        }
        const now = Date.now();
        if (now - lastInteractionAt < 30000) {
            return;
        }
        lastInteractionAt = now;
        ping(true);
    };

    const leave = () => {
        window.clearInterval(pingTimer);
        pingTimer = null;

        const echo = client();
        if (echo && subscribedUid) {
            echo.leave(`user.${subscribedUid}`);
            echo.leave('support.inbox');
            echo.leave('staff.chat');
        }

        subscribedUid = null;
    };

    const subscribe = () => {
        const userUid = uid();
        const echo = client();

        if (!userUid) {
            leave();
            return;
        }

        if (echo && subscribedUid === userUid) {
            return;
        }

        leave();
        subscribedUid = userUid;

        if (echo) {
            echo.private(`user.${userUid}`)
                .listen('.support.updated', (payload) => {
                    window.dispatchEvent(new CustomEvent('isabi:support-customer', { detail: payload }));
                })
                .listen('.support.typing', (payload) => {
                    window.dispatchEvent(new CustomEvent('isabi:support-typing', { detail: payload }));
                })
                .listen('.notification.received', (payload) => {
                    if (!payload?.item) {
                        return;
                    }

                    const current = page.props.notifications || { unread_count: 0, items: [] };
                    const items = [
                        payload.item,
                        ...(current.items || []).filter((item) => item.id !== payload.item.id),
                    ].slice(0, 12);

                    page.props.notifications = {
                        unread_count: payload.unread_count ?? current.unread_count,
                        items,
                    };

                    if (isStaff() && !page.props.auth?.user?.is_super_admin && !page.props.auth?.user?.restricted) {
                        window.dispatchEvent(new CustomEvent('isabi:ops-attention-refresh'));
                    }
                });

            if (isStaff()) {
                echo.private('support.inbox')
                    .listen('.support.inbox', (payload) => {
                        window.dispatchEvent(new CustomEvent('isabi:support-inbox', { detail: payload }));
                    })
                    .listen('.support.typing', (payload) => {
                        window.dispatchEvent(new CustomEvent('isabi:support-typing', { detail: payload }));
                    });

                echo.private('staff.chat')
                    .listen('.staff.chat', (payload) => {
                        window.dispatchEvent(new CustomEvent('isabi:staff-chat', { detail: payload }));
                    });
            }
        }

        ping(true);
        pingTimer = window.setInterval(ping, 25000);
    };

    onMounted(() => {
        subscribe();
        window.addEventListener('pointerdown', onInteract, { passive: true });
        window.addEventListener('keydown', onInteract);
        window.addEventListener('scroll', onInteract, { passive: true, capture: true });
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                onInteract();
            }
        });
    });
    onUnmounted(() => {
        leave();
        window.removeEventListener('pointerdown', onInteract);
        window.removeEventListener('keydown', onInteract);
        window.removeEventListener('scroll', onInteract, { capture: true });
    });
    watch(uid, subscribe);
}
