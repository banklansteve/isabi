/**
 * Show a Kraftrack toast from the client.
 *
 * @param {string|object} messageOrOptions
 * @param {'success'|'error'|'info'|'warning'} [type='success']
 * @param {number} [duration=4800]
 */
export function toast(messageOrOptions, type = 'success', duration = 4800) {
    const detail =
        typeof messageOrOptions === 'object' && messageOrOptions !== null
            ? {
                  type: messageOrOptions.type || 'success',
                  title: messageOrOptions.title || '',
                  message: messageOrOptions.message || '',
                  duration: messageOrOptions.duration || 4800,
              }
            : {
                  type,
                  message: messageOrOptions,
                  duration,
              };

    if (!detail.message) {
        return;
    }

    window.dispatchEvent(
        new CustomEvent('kraftrack:toast', {
            detail,
        }),
    );
}
