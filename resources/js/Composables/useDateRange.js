import { computed, ref, watch } from 'vue';
import { RANGE_PRESETS, filterSeries, inRange, rangeBounds } from '@/utils/adminRange';

export function useDateRange(defaultId = 'this_month') {
    const preset = ref(defaultId);
    const customFrom = ref('');
    const customTo = ref('');

    const bounds = computed(() => rangeBounds(preset.value, customFrom.value, customTo.value));

    const apply = (id) => {
        preset.value = id;
    };

    const matches = (iso) => inRange(iso, bounds.value);
    const series = (points) => filterSeries(points, bounds.value);

    watch([preset, customFrom, customTo], () => {}, { flush: 'sync' });

    return {
        preset,
        customFrom,
        customTo,
        bounds,
        presets: RANGE_PRESETS,
        apply,
        matches,
        series,
    };
}
