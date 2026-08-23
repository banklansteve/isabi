<template></template>

<script setup>
import { useAdminChrome } from '@/Composables/useAdminChrome';
import { toRef } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    eyebrow: { type: String, default: '' },
});

useAdminChrome(toRef(props, 'title'), toRef(props, 'eyebrow'));
</script>
