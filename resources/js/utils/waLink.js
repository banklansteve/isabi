/**
 * Build an HTTPS-only WhatsApp click-to-chat link (wa.me).
 *
 * Never returns a whatsapp:// or intent:// scheme — those trigger the
 * browser's un-styleable "Open WhatsApp?" alert. wa.me stays on HTTPS.
 *
 * Nigerian numbers are normalised to the international 234 format.
 *
 * @param {string} raw - the stored WhatsApp number
 * @param {string} [message] - optional pre-filled message
 * @returns {string} a wa.me URL, or '' when no usable number
 */
export function waLink(raw, message = '') {
    let digits = String(raw || '').replace(/\D+/g, '');

    if (!digits) {
        return '';
    }

    if (digits.startsWith('0')) {
        digits = `234${digits.slice(1)}`;
    } else if (digits.startsWith('234')) {
        // already international
    } else if (digits.length === 10) {
        digits = `234${digits}`;
    }

    const base = `https://wa.me/${digits}`;

    return message ? `${base}?text=${encodeURIComponent(message)}` : base;
}
