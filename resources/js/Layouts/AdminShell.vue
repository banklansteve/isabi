<template>
    <component :is="isSuper ? AdminLayout : OpsLayout">
        <slot />
    </component>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import OpsLayout from '@/Layouts/OpsLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const isSuper = computed(() => !!page.props.auth?.user?.is_super_admin);
</script>
