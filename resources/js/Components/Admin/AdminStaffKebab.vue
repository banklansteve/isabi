<template>
    <div ref="root" class="relative shrink-0">
        <button
            type="button"
            class="flex h-8 w-8 items-center justify-center rounded-lg text-ink/45 transition-colors duration-150 hover:bg-tint hover:text-deep"
            aria-label="More actions"
            :aria-expanded="open"
            @click.stop="$emit('toggle')"
        >
            <i class="ti ti-dots-vertical" aria-hidden="true" />
        </button>
        <AdminSlideMenu :open="open" width-class="mt-1 w-48">
            <button type="button" class="menu-item" @click="$emit('view')">View detail</button>
            <button v-if="person.status === 'invited'" type="button" class="menu-item" @click="$emit('resend')">
                Resend invite
            </button>
            <button
                v-if="person.status === 'invited'"
                type="button"
                class="menu-item text-red-600"
                @click="$emit('action', { type: 'revoke', person })"
            >
                Revoke invite
            </button>
            <button
                v-if="person.status === 'active' && !isSelf"
                type="button"
                class="menu-item text-red-600"
                @click="$emit('action', { type: 'disable', person })"
            >
                Disable
            </button>
            <button
                v-if="person.status === 'suspended' && !isSelf"
                type="button"
                class="menu-item"
                @click="$emit('action', { type: 'reinstate', person })"
            >
                Reinstate
            </button>
            <button
                v-if="person.status !== 'invited' && !isSelf"
                type="button"
                class="menu-item text-red-600"
                @click="$emit('action', { type: 'remove', person })"
            >
                Remove
            </button>
        </AdminSlideMenu>
    </div>
</template>

<script setup>
import AdminSlideMenu from '@/Components/Admin/AdminSlideMenu.vue';

defineProps({
    person: { type: Object, required: true },
    open: { type: Boolean, default: false },
    isSelf: { type: Boolean, default: false },
});

defineEmits(['toggle', 'view', 'resend', 'action']);
</script>

<style scoped>
.menu-item {
    @apply block w-full px-3 py-2 text-left text-[13px] font-semibold text-ink/70 transition-colors duration-150 hover:bg-pale;
}
</style>
