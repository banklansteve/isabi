/**
 * Client-side image downscale before upload.
 * Keeps camera JPEGs from shipping at 12MP+ when the product only needs ~2K.
 */

const DEFAULT_MAX_EDGE = 2048;
const DEFAULT_QUALITY = 0.82;
const MIN_BYTES_TO_COMPRESS = 350 * 1024;

/**
 * @param {File} file
 * @param {{ maxEdge?: number, quality?: number }} [options]
 * @returns {Promise<File>}
 */
export async function compressImageFile(file, options = {}) {
    if (!file || !file.type?.startsWith('image/')) {
        return file;
    }

    // GIFs (animation) and tiny files stay as-is.
    if (file.type === 'image/gif' || file.size < MIN_BYTES_TO_COMPRESS) {
        return file;
    }

    const maxEdge = options.maxEdge ?? DEFAULT_MAX_EDGE;
    const quality = options.quality ?? DEFAULT_QUALITY;

    let bitmap;
    try {
        bitmap = await createImageBitmap(file);
    } catch {
        return file;
    }

    const { width, height } = bitmap;
    const longest = Math.max(width, height);

    if (longest <= maxEdge && file.size < 1.5 * 1024 * 1024) {
        bitmap.close?.();
        return file;
    }

    const scale = longest > maxEdge ? maxEdge / longest : 1;
    const targetW = Math.max(1, Math.round(width * scale));
    const targetH = Math.max(1, Math.round(height * scale));

    const canvas = document.createElement('canvas');
    canvas.width = targetW;
    canvas.height = targetH;
    const ctx = canvas.getContext('2d', { alpha: false });
    if (!ctx) {
        bitmap.close?.();
        return file;
    }

    ctx.drawImage(bitmap, 0, 0, targetW, targetH);
    bitmap.close?.();

    const blob = await new Promise((resolve) => {
        canvas.toBlob(resolve, 'image/jpeg', quality);
    });

    if (!blob || blob.size >= file.size) {
        return file;
    }

    const baseName = file.name.replace(/\.[^.]+$/, '') || 'photo';

    return new File([blob], `${baseName}.jpg`, {
        type: 'image/jpeg',
        lastModified: Date.now(),
    });
}
