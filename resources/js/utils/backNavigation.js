const STORAGE_KEY = 'isabi:previousUrl';

export const rememberCurrentUrl = () => {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        sessionStorage.setItem(STORAGE_KEY, window.location.href);
    } catch {
        // Private mode or quota — fall back to document.referrer.
    }
};

export const previousInAppUrl = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    const candidates = [];

    try {
        const stored = sessionStorage.getItem(STORAGE_KEY);
        if (stored) {
            candidates.push(stored);
        }
    } catch {
        // ignore
    }

    if (document.referrer) {
        candidates.push(document.referrer);
    }

    const here = window.location;

    for (const raw of candidates) {
        try {
            const url = new URL(raw, here.origin);

            if (url.origin !== here.origin) {
                continue;
            }

            if (url.pathname === here.pathname) {
                continue;
            }

            return url.href;
        } catch {
            // skip invalid candidates
        }
    }

    return null;
};

export const resolveBackTarget = ({ fallbackHref, fallbackLabel, ownSlug } = {}) => {
    const href = previousInAppUrl();

    if (!href) {
        return { href: fallbackHref, label: fallbackLabel };
    }

    try {
        const path = new URL(href).pathname.replace(/\/+$/, '') || '/';

        if (path === '/work-log') {
            return { href, label: 'Work log' };
        }

        if (path === '/my-page' || (ownSlug && path === `/p/${ownSlug}`)) {
            return { href, label: 'My page' };
        }

        if (path.startsWith('/p/')) {
            return { href, label: 'Profile' };
        }

        if (path === '/dashboard') {
            return { href, label: 'Home' };
        }

        return { href, label: 'Back' };
    } catch {
        return { href: fallbackHref, label: fallbackLabel };
    }
};
