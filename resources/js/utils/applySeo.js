const upsertMeta = (selector, attributes) => {
    let el = document.head.querySelector(selector);
    if (!el) {
        el = document.createElement('meta');
        document.head.appendChild(el);
    }
    Object.entries(attributes).forEach(([key, value]) => {
        el.setAttribute(key, value);
    });
};

const upsertLink = (rel, href) => {
    let el = document.head.querySelector(`link[rel="${rel}"]`);
    if (!el) {
        el = document.createElement('link');
        el.setAttribute('rel', rel);
        document.head.appendChild(el);
    }
    el.setAttribute('href', href);
};

/**
 * Keep description / canonical / robots in sync on Inertia visits.
 * The first HTML response already has these from Blade; this covers
 * subsequent client-side navigations.
 */
export const applySeo = (seo) => {
    if (typeof document === 'undefined' || !seo) {
        return;
    }

    if (seo.description) {
        upsertMeta('meta[name="description"]', {
            name: 'description',
            content: seo.description,
        });
        upsertMeta('meta[property="og:description"]', {
            property: 'og:description',
            content: seo.description,
        });
    }

    if (seo.canonical) {
        upsertLink('canonical', seo.canonical);
        upsertMeta('meta[property="og:url"]', {
            property: 'og:url',
            content: seo.canonical,
        });
    }

    if (seo.title) {
        upsertMeta('meta[property="og:title"]', {
            property: 'og:title',
            content: seo.title,
        });
    }

    upsertMeta('meta[name="robots"]', {
        name: 'robots',
        content: seo.noindex
            ? 'noindex, nofollow'
            : 'index, follow, max-image-preview:large, max-snippet:-1',
    });

    if (seo.image) {
        upsertMeta('meta[property="og:image"]', {
            property: 'og:image',
            content: seo.image,
        });
    }
};
