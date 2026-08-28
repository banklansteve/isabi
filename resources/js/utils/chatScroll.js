import { nextTick } from 'vue';

export function scrollChatToEnd(scroller, pin) {
    const run = () => {
        const el = scroller?.value;

        if (pin?.value) {
            pin.value.scrollIntoView({ block: 'end', inline: 'nearest', behavior: 'instant' });
        }

        if (el) {
            el.scrollTop = el.scrollHeight;
        }
    };

    nextTick(() => {
        run();
        requestAnimationFrame(run);
        window.setTimeout(run, 80);
        window.setTimeout(run, 240);
    });
}
