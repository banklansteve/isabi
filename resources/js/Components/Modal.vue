<template>
    <!--
      Backward-compatible wrapper around AppModal.
      Prefer importing AppModal directly in new code.
    -->
    <AppModal
        :show="show"
        :closeable="closeable"
        :size="mappedSize"
        @close="emit('close')"
    >
        <slot />
    </AppModal>
</template>

<script setup>
import AppModal from '@/Components/App/AppModal.vue';
import { computed } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    maxWidth: { type: String, default: '2xl' },
    closeable: { type: Boolean, default: true },
});

const emit = defineEmits(['close']);

const mappedSize = computed(() => {
    const map = {
        sm: 'sm',
        md: 'md',
        lg: 'lg',
        xl: 'xl',
        '2xl': 'xl',
    };
    return map[props.maxWidth] || 'md';
});
</script>
