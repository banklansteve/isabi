import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

let echo = null;

export function echoClient(config = null) {
    if (echo) {
        return echo;
    }

    if (typeof window === 'undefined') {
        return null;
    }

    const key = config?.key || import.meta.env.VITE_REVERB_APP_KEY;

    if (!key || config?.enabled === false) {
        return null;
    }

    window.Pusher = Pusher;

    const scheme = config?.scheme || import.meta.env.VITE_REVERB_SCHEME || window.location.protocol.replace(':', '');
    const tls = scheme === 'https';
    const port = Number(config?.port || import.meta.env.VITE_REVERB_PORT || (tls ? 443 : 8080));

    echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
        wsPort: port,
        wssPort: port,
        forceTLS: tls,
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        withCredentials: true,
        disableStats: true,
        auth: {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        },
    });

    return echo;
}

export function echoConnected() {
    const state = echo?.connector?.pusher?.connection?.state;

    return state === 'connected';
}
