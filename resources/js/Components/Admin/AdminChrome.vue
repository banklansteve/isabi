<template></template>

<script setup>
import { useAdminChrome } from '@/Composables/useAdminChrome';
import { toRef } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    eyebrow: { type: String, default: '' },
    backHref: { type: String, default: '' },
    backLabel: { type: String, default: 'Back' },
});

useAdminChrome(
    toRef(props, 'title'),
    toRef(props, 'eyebrow'),
    toRef(props, 'backHref'),
    toRef(props, 'backLabel'),
);
</script>
