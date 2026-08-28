<template>
    <span class="inline-flex items-center gap-1" :class="labelClass" role="status">
        <span class="typing-dots" aria-hidden="true">
            <span />
            <span />
            <span />
        </span>
        <span v-if="label" class="text-[11px] font-medium">{{ label }}</span>
    </span>
</template>

<script setup>
defineProps({
    label: { type: String, default: '' },
    labelClass: { type: String, default: 'text-ink/40' },
});
</script>

<style scoped>
.typing-dots {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    height: 1rem;
}
.typing-dots span {
    display: block;
    width: 4px;
    height: 4px;
    border-radius: 999px;
    background: currentColor;
    animation: typing-dot 1.1s ease-in-out infinite;
}
.typing-dots span:nth-child(2) {
    animation-delay: 0.15s;
}
.typing-dots span:nth-child(3) {
    animation-delay: 0.3s;
}
@keyframes typing-dot {
    0%,
    80%,
    100% {
        opacity: 0.25;
        transform: translateY(0);
    }
    40% {
        opacity: 1;
        transform: translateY(-2px);
    }
}
@media (prefers-reduced-motion: reduce) {
    .typing-dots span {
        animation: none;
        opacity: 0.55;
    }
}
</style>
