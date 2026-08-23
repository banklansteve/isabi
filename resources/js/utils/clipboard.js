/**
 * Copy text to the clipboard with a fallback for non-secure contexts
 * (e.g. http:// local sites where navigator.clipboard is unavailable).
 *
 * @param {string} text
 * @returns {Promise<boolean>}
 */
export async function copyToClipboard(text) {
    const value = String(text ?? '').trim();
    if (!value) {
        return false;
    }

    if (typeof navigator !== 'undefined' && navigator.clipboard?.writeText) {
        try {
            await navigator.clipboard.writeText(value);
            return true;
        } catch {
            // Fall through to legacy path.
        }
    }

    try {
        const input = document.createElement('textarea');
        input.value = value;
        input.setAttribute('readonly', '');
        input.style.position = 'fixed';
        input.style.top = '0';
        input.style.left = '0';
        input.style.width = '1px';
        input.style.height = '1px';
        input.style.padding = '0';
        input.style.border = 'none';
        input.style.outline = 'none';
        input.style.boxShadow = 'none';
        input.style.background = 'transparent';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.focus();
        input.select();
        input.setSelectionRange(0, value.length);
        const ok = document.execCommand('copy');
        document.body.removeChild(input);
        return ok;
    } catch {
        return false;
    }
}
