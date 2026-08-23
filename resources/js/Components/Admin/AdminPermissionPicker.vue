<template>
    <div class="space-y-3">
        <div
            v-for="group in groups"
            :key="group.id"
            class="overflow-hidden rounded-xl border border-ink/[0.08]"
        >
            <div class="flex items-center justify-between gap-3 bg-pale/70 px-3 py-2.5">
                <label class="flex min-w-0 items-center gap-2.5 text-[13px] font-bold text-ink">
                    <input
                        :ref="(el) => setGroupEl(group.id, el)"
                        type="checkbox"
                        class="rounded border-ink/20 text-base-action focus:ring-base/20"
                        :checked="allInGroup(group)"
                        :disabled="disabled"
                        :aria-label="`Select all in ${group.label}`"
                        @change="toggleGroup(group, $event.target.checked)"
                    />
                    {{ group.label }}
                </label>
                <span class="shrink-0 text-[11px] font-semibold tabular-nums text-ink/35">
                    {{ selectedInGroup(group) }}/{{ group.items.length }}
                </span>
            </div>
            <ul class="divide-y divide-ink/[0.05] px-3 py-1">
                <li v-for="item in group.items" :key="item.key">
                    <label
                        class="flex items-start gap-2.5 py-2 text-[13px] font-medium text-ink/70"
                        :class="disabled ? 'cursor-default' : 'cursor-pointer'"
                    >
                        <input
                            type="checkbox"
                            class="mt-0.5 rounded border-ink/20 text-base-action focus:ring-base/20"
                            :checked="modelValue.includes(item.key)"
                            :disabled="disabled"
                            @change="toggleKey(item.key, $event.target.checked)"
                        />
                        <span>{{ item.label }}</span>
                    </label>
                </li>
            </ul>
        </div>
        <p v-if="!groups.length" class="text-[13px] font-medium text-ink/40">No permissions defined.</p>
    </div>
</template>

<script setup>
import { nextTick, watch } from 'vue';

const props = defineProps({
    groups: { type: Array, default: () => [] },
    modelValue: { type: Array, default: () => [] },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const groupEls = {};

const keysOf = (group) => (group.items || []).map((item) => item.key);

const selectedInGroup = (group) => keysOf(group).filter((key) => props.modelValue.includes(key)).length;

const allInGroup = (group) => {
    const keys = keysOf(group);
    return keys.length > 0 && keys.every((key) => props.modelValue.includes(key));
};

const someInGroup = (group) => {
    const count = selectedInGroup(group);
    return count > 0 && count < keysOf(group).length;
};

const setGroupEl = (id, el) => {
    groupEls[id] = el;
    if (el) {
        const group = props.groups.find((item) => item.id === id);
        el.indeterminate = group ? someInGroup(group) : false;
    }
};

const syncIndeterminate = () => {
    nextTick(() => {
        props.groups.forEach((group) => {
            const el = groupEls[group.id];
            if (el) {
                el.indeterminate = someInGroup(group);
            }
        });
    });
};

watch(
    () => [props.modelValue, props.groups],
    () => syncIndeterminate(),
    { deep: true, immediate: true },
);

const toggleKey = (key, on) => {
    if (props.disabled) {
        return;
    }
    const current = new Set(props.modelValue);
    if (on) {
        current.add(key);
    } else {
        current.delete(key);
    }
    emit('update:modelValue', [...current]);
};

const toggleGroup = (group, on) => {
    if (props.disabled) {
        return;
    }
    const current = new Set(props.modelValue);
    keysOf(group).forEach((key) => {
        if (on) {
            current.add(key);
        } else {
            current.delete(key);
        }
    });
    emit('update:modelValue', [...current]);
};
</script>
