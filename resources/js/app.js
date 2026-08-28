import '../css/app.css';
import './bootstrap';

import CookieConsent from './Components/CookieConsent.vue';
import AppNavigationProgress from './Components/App/AppNavigationProgress.vue';
import RealtimeBridge from './Components/App/RealtimeBridge.vue';
import SupportFab from './Components/App/SupportFab.vue';
import ToastHost from './Components/ToastHost.vue';
import { applySeo } from './utils/applySeo';
import { rememberCurrentUrl } from './utils/backNavigation';
import AdminShell from './Layouts/AdminShell.vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Isabi';

const brandedTitle = (title) => {
    if (!title) {
        return appName;
    }

    return title.includes(appName) ? title : `${title} · ${appName}`;
};

createInertiaApp({
    title: brandedTitle,
    resolve: async (name) => {
        const page = await resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        );

        if (name.startsWith('Admin/') && !name.startsWith('Admin/Auth/')) {
            page.default.layout ??= AdminShell;
        }

        return page;
    },
    setup({ el, App, props, plugin }) {
        applySeo(props.initialPage?.props?.seo ?? props.page?.props?.seo);

        return createApp({
            render: () =>
                h('div', { id: 'app-root' }, [
                    h(App, props),
                    h(CookieConsent),
                    h(ToastHost),
                    h(RealtimeBridge),
                    h(SupportFab),
                    h(AppNavigationProgress),
                ]),
        })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    // Custom AppNavigationProgress owns the visit indicator.
    progress: false,
});

router.on('before', (event) => {
    rememberCurrentUrl();

    const visit = event.detail?.visit;
    const url = String(visit?.url || '');

    if (visit && url.includes('/admin')) {
        visit.showProgress = false;
    }
});

router.on('success', (event) => {
    applySeo(event.detail.page.props.seo);
});
