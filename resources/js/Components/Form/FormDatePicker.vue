<template>
    <FormField :id="id" :label="label" :hint="hint" :error="error">
        <template #default="{ id: fieldId, describedBy }">
            <div ref="rootRef" class="relative">
                <button
                    :id="fieldId"
                    type="button"
                    class="form-control form-select-trigger pe-11"
                    :class="[
                        icon ? 'ps-11' : 'ps-4',
                        {
                            'has-error': !!error,
                            'is-open': open,
                        },
                    ]"
                    :disabled="disabled"
                    :aria-expanded="open"
                    aria-haspopup="dialog"
                    :aria-describedby="describedBy"
                    @click="toggle"
                >
                    <i
                        v-if="icon"
                        class="form-control-icon"
                        :class="icon"
                        aria-hidden="true"
                    />
                    <span class="truncate text-ink">{{ displayLabel }}</span>
                    <i
                        class="ti ti-calendar absolute right-3.5 top-1/2 -translate-y-1/2 text-lg text-ink/30 transition-colors"
                        :class="{ 'text-base': open }"
                        aria-hidden="true"
                    />
                </button>

                <Transition name="select-panel">
                    <div
                        v-if="open"
                        class="form-select-panel p-3 sm:p-4"
                        role="dialog"
                        aria-label="Choose date"
                    >
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <button
                                type="button"
                                class="tap-target flex h-9 w-9 items-center justify-center rounded-xl text-ink/50 transition-colors hover:bg-pale hover:text-ink disabled:opacity-30"
                                :disabled="!canGoPrev"
                                aria-label="Previous month"
                                @click="shiftMonth(-1)"
                            >
                                <i class="ti ti-chevron-left text-lg" aria-hidden="true" />
                            </button>
                            <p class="text-sm font-semibold tracking-tight text-ink">
                                {{ monthLabel }}
                            </p>
                            <button
                                type="button"
                                class="tap-target flex h-9 w-9 items-center justify-center rounded-xl text-ink/50 transition-colors hover:bg-pale hover:text-ink disabled:opacity-30"
                                :disabled="!canGoNext"
                                aria-label="Next month"
                                @click="shiftMonth(1)"
                            >
                                <i class="ti ti-chevron-right text-lg" aria-hidden="true" />
                            </button>
                        </div>

                        <div class="mb-1.5 grid grid-cols-7 gap-1">
                            <span
                                v-for="day in weekdays"
                                :key="day"
                                class="py-1 text-center text-[10px] font-semibold uppercase tracking-wide text-ink/35"
                            >
                                {{ day }}
                            </span>
                        </div>

                        <div class="grid grid-cols-7 gap-1">
                            <button
                                v-for="(cell, index) in cells"
                                :key="`${cell.iso}-${index}`"
                                type="button"
                                class="tap-target flex h-9 items-center justify-center rounded-xl text-sm font-medium transition-colors duration-150"
                                :class="cellClass(cell)"
                                :disabled="!cell.inRange"
                                @click="selectDay(cell)"
                            >
                                {{ cell.day }}
                            </button>
                        </div>

                        <button
                            v-if="todayInRange"
                            type="button"
                            class="mt-3 w-full rounded-xl bg-pale py-2 text-xs font-semibold text-deep transition-colors hover:bg-tint"
                            @click="selectToday"
                        >
                            Use today
                        </button>
                    </div>
                </Transition>
            </div>
        </template>
    </FormField>
</template>

<script setup>
import FormField from '@/Components/Form/FormField.vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const model = defineModel({ type: String, default: '' });

const props = defineProps({
    id: { type: String, default: '' },
    label: { type: String, default: '' },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
    icon: { type: String, default: 'ti ti-calendar' },
    minDate: { type: String, required: true },
    maxDate: { type: String, required: true },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['change', 'blur']);

const open = ref(false);
const rootRef = ref(null);
const view = ref(startOfMonth(parseISO(model.value || props.maxDate)));

const weekdays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

const min = computed(() => parseISO(props.minDate));
const max = computed(() => parseISO(props.maxDate));

const monthLabel = computed(() =>
    view.value.toLocaleDateString(undefined, { month: 'long', year: 'numeric' }),
);

const displayLabel = computed(() => {
    if (!model.value) {
        return 'Select date';
    }
    const d = parseISO(model.value);
    return d.toLocaleDateString(undefined, {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
});

const todayInRange = computed(() => {
    const today = startOfDay(new Date());
    return today >= min.value && today <= max.value;
});

const canGoPrev = computed(() => {
    const prev = new Date(view.value.getFullYear(), view.value.getMonth() - 1, 1);
    return endOfMonth(prev) >= min.value;
});

const canGoNext = computed(() => {
    const next = new Date(view.value.getFullYear(), view.value.getMonth() + 1, 1);
    return next <= max.value;
});

const cells = computed(() => {
    const year = view.value.getFullYear();
    const month = view.value.getMonth();
    const first = new Date(year, month, 1);
    const startPad = first.getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const items = [];

    for (let i = 0; i < startPad; i++) {
        const d = new Date(year, month, i - startPad + 1);
        items.push(makeCell(d, false));
    }
    for (let day = 1; day <= daysInMonth; day++) {
        items.push(makeCell(new Date(year, month, day), true));
    }
    while (items.length % 7 !== 0) {
        const last = items[items.length - 1];
        const d = parseISO(last.iso);
        d.setDate(d.getDate() + 1);
        items.push(makeCell(d, false));
    }

    return items;
});

function makeCell(date, currentMonth) {
    const dayStart = startOfDay(date);
    const iso = toISO(dayStart);
    return {
        day: date.getDate(),
        iso,
        currentMonth,
        inRange: dayStart >= min.value && dayStart <= max.value,
        selected: model.value === iso,
        isToday: toISO(startOfDay(new Date())) === iso,
    };
}

function cellClass(cell) {
    if (!cell.currentMonth) {
        return 'text-ink/15';
    }
    if (!cell.inRange) {
        return 'cursor-not-allowed text-ink/20';
    }
    if (cell.selected) {
        return 'bg-ink text-white shadow-sm';
    }
    if (cell.isToday) {
        return 'bg-tint text-deep hover:bg-base/15';
    }
    return 'text-ink/75 hover:bg-pale';
}

const toggle = () => {
    if (props.disabled) {
        return;
    }
    open.value = !open.value;
    if (open.value && model.value) {
        view.value = startOfMonth(parseISO(model.value));
    }
};

const close = () => {
    if (!open.value) {
        return;
    }
    open.value = false;
    emit('blur');
};

const selectDay = (cell) => {
    if (!cell.inRange || !cell.currentMonth) {
        return;
    }
    model.value = cell.iso;
    emit('change', cell.iso);
    close();
};

const selectToday = () => {
    const iso = toISO(startOfDay(new Date()));
    model.value = iso;
    emit('change', iso);
    close();
};

const shiftMonth = (delta) => {
    view.value = new Date(view.value.getFullYear(), view.value.getMonth() + delta, 1);
};

const onPointerDown = (event) => {
    if (!rootRef.value?.contains(event.target)) {
        close();
    }
};

watch(
    () => props.maxDate,
    () => {
        if (!model.value) {
            view.value = startOfMonth(max.value);
        }
    },
);

onMounted(() => document.addEventListener('pointerdown', onPointerDown));
onBeforeUnmount(() => document.removeEventListener('pointerdown', onPointerDown));

function parseISO(value) {
    const [y, m, d] = String(value).split('-').map(Number);
    return startOfDay(new Date(y, (m || 1) - 1, d || 1));
}

function toISO(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function startOfDay(date) {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
}

function startOfMonth(date) {
    return new Date(date.getFullYear(), date.getMonth(), 1);
}

function endOfMonth(date) {
    return new Date(date.getFullYear(), date.getMonth() + 1, 0);
}
</script>
