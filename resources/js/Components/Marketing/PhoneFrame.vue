<template>
    <div
        class="phone-frame relative mx-auto w-[min(18.25rem,100%)] select-none sm:w-[min(19rem,100%)]"
        aria-hidden="true"
    >
        <div
            class="relative overflow-hidden rounded-[2.1rem] bg-[#0a1628] p-[0.6rem] shadow-[0_28px_60px_-20px_rgba(7,20,39,0.55)] ring-1 ring-ink/20"
        >
            <div
                class="relative overflow-hidden rounded-[1.65rem] bg-pale"
                :style="{ aspectRatio: '9 / 19.4' }"
            >
                <!-- Status bar -->
                <div
                    class="absolute inset-x-0 top-0 z-20 flex items-center justify-between px-5 pb-1 pt-2.5 text-[9px] font-bold"
                    :class="lightStatus ? 'text-ink/70' : 'text-white/90'"
                >
                    <span>9:41</span>
                    <div
                        class="absolute left-1/2 top-1.5 h-[1.15rem] w-[5.25rem] -translate-x-1/2 rounded-full bg-ink/90"
                    />
                    <span class="inline-flex items-center gap-1">
                        <i class="ti ti-antenna-bars-5 text-[10px]" />
                        <i class="ti ti-wifi text-[10px]" />
                        <i class="ti ti-battery-4 text-[11px]" />
                    </span>
                </div>

                <div class="absolute inset-0 overflow-hidden">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    lightStatus: { type: Boolean, default: false },
});
</script>
