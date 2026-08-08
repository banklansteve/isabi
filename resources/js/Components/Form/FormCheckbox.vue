<template>
    <label class="inline-flex cursor-pointer items-start gap-3 select-none" :class="{ 'opacity-50': disabled }">
        <span class="relative mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center">
            <input
                v-model="model"
                type="checkbox"
                class="absolute inset-0 z-10 cursor-pointer opacity-0 disabled:cursor-not-allowed"
                :name="name"
                :value="value"
                :disabled="disabled"
                :required="required"
            />
            <span
                class="flex h-5 w-5 items-center justify-center rounded-md border transition-all duration-150"
                :class="
                    model
                        ? 'border-base bg-base shadow-[0_0_0_3px_rgba(47,111,237,0.12)]'
                        : 'border-ink/15 bg-white'
                "
            >
                <i
                    class="ti ti-check text-xs text-white transition-opacity duration-150"
                    :class="model ? 'opacity-100' : 'opacity-0'"
                    aria-hidden="true"
                />
            </span>
        </span>
        <span class="text-sm font-medium leading-snug text-ink/65">
            <slot>{{ label }}</slot>
        </span>
    </label>
</template>

<script setup>
const model = defineModel({ type: Boolean, default: false });

defineProps({
    label: { type: String, default: '' },
    name: { type: String, default: undefined },
    value: { type: [String, Number, Boolean], default: true },
    disabled: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
});
</script>
