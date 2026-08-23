<template>
    <Transition name="admin-menu">
        <div
            v-if="open"
            class="absolute z-20 overflow-hidden rounded-xl bg-white py-1 shadow-premium ring-1 ring-ink/[0.08]"
            :class="[align === 'left' ? 'left-0' : 'right-0', widthClass]"
            :role="role"
            @click.stop
        >
            <slot />
        </div>
    </Transition>
</template>

<script setup>
defineProps({
    open: { type: Boolean, default: false },
    align: { type: String, default: 'right' },
    widthClass: { type: String, default: 'mt-1 w-52' },
    role: { type: String, default: 'menu' },
});
</script>

<style>
.admin-menu-enter-active,
.admin-menu-leave-active {
    transform-origin: top right;
    transition:
        opacity 0.18s cubic-bezier(0.32, 0.72, 0, 1),
        transform 0.22s cubic-bezier(0.32, 0.72, 0, 1);
}
.admin-menu-enter-from,
.admin-menu-leave-to {
    opacity: 0;
    transform: translate3d(0, -6px, 0) scale(0.96);
}
</style>
