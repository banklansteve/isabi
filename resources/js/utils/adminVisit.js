import { router } from '@inertiajs/vue3';

export const ADMIN_CACHE_FOR = ['30s', '5m'];

const queued = new Set();
let timer = null;

export function adminPath(href) {
    try {
        const url = new URL(href, window.location.origin);
        return `${url.pathname}${url.search}`;
    } catch {
        return String(href || '');
    }
}

export function isAdminHref(href) {
    return adminPath(href).startsWith('/admin');
}

export function currentAdminPath() {
    return `${window.location.pathname}${window.location.search}`;
}

export function prefetchAdmin(href) {
    const path = adminPath(href);

    if (!path || path === '#' || !isAdminHref(path) || path === currentAdminPath()) {
        return;
    }

    if (router.getCached(href) || router.getPrefetching(href) || queued.has(path)) {
        return;
    }

    queued.add(path);
    router.prefetch(href, { showProgress: false }, { cacheFor: ADMIN_CACHE_FOR });
}

export function prefetchAdminSoon(hrefs, delay = 0) {
    window.clearTimeout(timer);
    timer = window.setTimeout(() => {
        hrefs.filter(Boolean).forEach((href) => prefetchAdmin(href));
    }, delay);
}

export function visitAdmin(href, options = {}) {
    router.visit(href, {
        showProgress: false,
        preserveScroll: false,
        ...options,
    });
}
