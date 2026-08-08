export function toast(message, type = 'success', duration = 4200) {
    window.dispatchEvent(
        new CustomEvent('isabi:toast', {
            detail: { message, type, duration },
        }),
    );
}
