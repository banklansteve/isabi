<template>
    <div class="flex flex-col gap-2">
        <div class="no-scrollbar flex gap-1.5 overflow-x-auto">
            <button
                v-for="item in range.presets"
                :key="item.id"
                type="button"
                class="shrink-0 rounded-full px-3 py-1.5 text-[12px] font-semibold transition-all duration-150 active:scale-[0.97]"
                :class="
                    preset === item.id
                        ? 'bg-base-action text-white shadow-sm'
                        : 'bg-white text-ink/50 ring-1 ring-ink/[0.06] hover:bg-tint hover:text-deep'
                "
                @click="range.apply(item.id)"
            >
                {{ item.label }}
            </button>
        </div>
        <div v-if="preset === 'custom'" class="flex flex-wrap items-end gap-2">
            <div class="min-w-[10.5rem] flex-1">
                <FormDatePicker
                    v-model="customFrom"
                    label="From"
                    :min-date="DATE_MIN"
                    :max-date="DATE_MAX"
                />
            </div>
            <span class="hidden pb-3 text-[12px] font-semibold text-ink/35 sm:inline">to</span>
            <div class="min-w-[10.5rem] flex-1">
                <FormDatePicker
                    v-model="customTo"
                    label="To"
                    :min-date="DATE_MIN"
                    :max-date="DATE_MAX"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import FormDatePicker from '@/Components/Form/FormDatePicker.vue';
import { computed, unref } from 'vue';

const DATE_MIN = '2000-01-01';
const DATE_MAX = (() => {
    const d = new Date();
    d.setFullYear(d.getFullYear() + 2);
    return d.toISOString().slice(0, 10);
})();

const props = defineProps({
    range: { type: Object, required: true },
});

const preset = computed(() => unref(props.range.preset));

const customFrom = computed({
    get: () => unref(props.range.customFrom) || '',
    set: (value) => {
        props.range.customFrom.value = value;
    },
});

const customTo = computed({
    get: () => unref(props.range.customTo) || '',
    set: (value) => {
        props.range.customTo.value = value;
    },
});
</script>

<style scoped>
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>
