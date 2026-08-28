<template>
    <ul v-if="roles.length" class="flex flex-wrap gap-1.5">
                <li
                    v-for="role in roles"
                    :key="role.slug"
                    class="rounded-full px-2.5 py-1 text-[11px] font-bold tracking-tight"
                    :class="dutyToneClass(role.tone)"
                >
            {{ role.short }}
        </li>
    </ul>
</template>

<script setup>
import { dutyToneClass } from '@/utils/opsStatus';

defineProps({
    roles: { type: Array, default: () => [] },
});
</script>
