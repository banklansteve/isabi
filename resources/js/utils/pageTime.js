/**
 * Client-side page dwell tracking for artisan sessions.
 * Sends page.duration beacons on Inertia navigations and tab hide.
 */
import { router } from '@inertiajs/vue3';

const PAGE_MAP = [
    [/\/dashboard/i, 'dashboard'],
    [/\/work-log/i, 'work-log'],
    [/\/profile/i, 'profile'],
    [/\/tokens|\/credits/i, 'credits'],
    [/\/help/i, 'help'],
    [/\/p\//i, 'public-profile'],
];

const classify = (path) => {
    for (const [re, key] of PAGE_MAP) {
        if (re.test(path)) return key;
    }
    return null;
};

const xsrfToken = () => {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
};

let startedAt = Date.now();
let currentPath = typeof window !== 'undefined' ? window.location.pathname : '/';
let currentPage = classify(currentPath);
let bound = false;

const flush = () => {
    if (!currentPage) return;
    const seconds = Math.round((Date.now() - startedAt) / 1000);
    if (seconds < 3) return;

    const payload = JSON.stringify({
        page: currentPage,
        seconds: Math.min(3600, seconds),
        path: currentPath,
    });

    fetch('/analytics/beacon/page-duration', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': xsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: payload,
        credentials: 'same-origin',
        keepalive: true,
    }).catch(() => {});
};

const reset = (path) => {
    currentPath = path;
    currentPage = classify(path);
    startedAt = Date.now();
};

export const startPageTimeTracking = () => {
    if (bound || typeof window === 'undefined') return;
    bound = true;

    reset(window.location.pathname);

    router.on('navigate', (event) => {
        flush();
        const url = event?.detail?.page?.url || window.location.pathname;
        const path = String(url).split('?')[0];
        reset(path.startsWith('http') ? new URL(path).pathname : path);
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            flush();
        } else {
            startedAt = Date.now();
        }
    });

    window.addEventListener('pagehide', flush);
};
