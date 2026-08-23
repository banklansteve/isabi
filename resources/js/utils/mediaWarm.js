/**
 * Start decoding a lightbox-sized image on click, so the overlay can fade
 * in without competing with a full-resolution decode on the same frames.
 */
export const warmMediaItem = (item) => {
    if (!item || item.kind === 'video') {
        return;
    }

    const src = item.preview_url || item.url;
    if (!src) {
        return;
    }

    const img = new Image();
    img.decoding = 'async';
    img.src = src;
    if (typeof img.decode === 'function') {
        img.decode().catch(() => {});
    }
};
