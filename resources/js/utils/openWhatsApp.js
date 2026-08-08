/**
 * Launch WhatsApp using HTTPS click-to-chat only (https://wa.me/…).
 *
 * Critical: never use whatsapp:// or intent:// from this app.
 * Those custom schemes always trigger the browser’s native
 * “Open WhatsApp?” confirmation — a second dialog we cannot style
 * or suppress. Our AppModal is the only confirmation in the flow.
 *
 * Also avoid web.whatsapp.com/send as the primary href: with WhatsApp
 * Desktop installed it often redirects into whatsapp:// and shows the
 * same browser alert. wa.me is WhatsApp’s official universal link and
 * stays on HTTPS from our site.
 *
 * Mobile  → opens WhatsApp / WhatsApp Business via App Links
 * Desktop → continues in the browser (WhatsApp Web) without a protocol alert
 */

export function isMobileDevice() {
    if (typeof navigator === 'undefined') {
        return false;
    }

    const ua = navigator.userAgent || '';
    if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile|SamsungBrowser/i.test(ua)) {
        return true;
    }

    return navigator.platform === 'MacIntel' && (navigator.maxTouchPoints || 0) > 1;
}

/**
 * HTTPS-only launch URL. protocolUrl is ignored on purpose.
 *
 * @param {{ appUrl?: string, webUrl?: string, protocolUrl?: string }} urls
 * @returns {string}
 */
export function whatsappLaunchHref({ appUrl = '', webUrl = '', protocolUrl: _protocolUrl = '' }) {
    // Prefer wa.me everywhere. Do not use custom protocols or web.whatsapp.com/send here.
    return appUrl || webUrl || '';
}
