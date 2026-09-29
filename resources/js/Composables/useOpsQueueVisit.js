import { prefetchAdmin } from '@/utils/adminVisit';
import { router } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';

/**
 * Smooth filter/tab switching for ops queue pages.
 * Updates the active chip immediately, reloads only the listed props,
 * and soft-fades the list while the request is in flight.
 */
export function useOpsQueueVisit(options) {
    const {
        routeName,
        only,
        activeKey,
        paramName,
        extraParams = () => ({}),
        prefetchKeys = [],
        prefetchParam = null,
    } = options;

    const pending = ref(false);
    const active = ref(activeKey.value);

    watch(activeKey, (value) => {
        if (!pending.value) {
            active.value = value;
        }
    });

    const hrefFor = (params) => route(routeName, {
        ...extraParams(),
        ...params,
    });

    const visit = (params = {}) => {
        const nextActive = params[paramName];
        if (nextActive != null) {
            active.value = nextActive;
        }

        pending.value = true;

        router.get(hrefFor(params), {}, {
            only,
            preserveScroll: true,
            preserveState: true,
            replace: true,
            showProgress: false,
            onFinish: () => {
                pending.value = false;
                active.value = activeKey.value;
            },
        });
    };

    onMounted(() => {
        if (!prefetchParam || !prefetchKeys.length) {
            return;
        }

        prefetchKeys.forEach((key) => {
            if (key === activeKey.value) {
                return;
            }

            prefetchAdmin(hrefFor({
                ...extraParams(),
                [prefetchParam]: key,
            }));
        });
    });

    return { active, pending, visit, hrefFor };
}
