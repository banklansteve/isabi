import { reactive, unref, watchEffect } from 'vue';

export const adminChrome = reactive({
    title: '',
    eyebrow: '',
});

export function useAdminChrome(title, eyebrow = '') {
    watchEffect(() => {
        adminChrome.title = String(unref(title) || '');
        adminChrome.eyebrow = String(unref(eyebrow) || '');
    });
}
