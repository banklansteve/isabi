import { reactive, unref, watchEffect } from 'vue';

export const adminChrome = reactive({
    title: '',
    eyebrow: '',
    backHref: '',
    backLabel: 'Back',
});

export function useAdminChrome(title, eyebrow = '', backHref = '', backLabel = 'Back') {
    watchEffect(() => {
        adminChrome.title = String(unref(title) || '');
        adminChrome.eyebrow = String(unref(eyebrow) || '');
        adminChrome.backHref = String(unref(backHref) || '');
        adminChrome.backLabel = String(unref(backLabel) || 'Back');
    });
}
