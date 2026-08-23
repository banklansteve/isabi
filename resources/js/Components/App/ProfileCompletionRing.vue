<template>
    <div v-if="showRing" class="relative">
        <button
            type="button"
            class="tap-target relative flex h-10 w-10 items-center justify-center rounded-full outline-none transition-opacity hover:opacity-90 focus-visible:ring-2 focus-visible:ring-base/30"
            :aria-label="`Profile ${completion}% complete — view checklist`"
            title="Complete your profile"
            @click="open = true"
        >
            <svg class="h-10 w-10 -rotate-90" viewBox="0 0 36 36" aria-hidden="true">
                <circle
                    cx="18"
                    cy="18"
                    r="15.5"
                    fill="none"
                    stroke="rgba(11,31,58,0.08)"
                    stroke-width="2.75"
                />
                <circle
                    cx="18"
                    cy="18"
                    r="15.5"
                    fill="none"
                    stroke="#1A4FB5"
                    stroke-width="2.75"
                    stroke-linecap="round"
                    :stroke-dasharray="ring.circumference"
                    :stroke-dashoffset="ring.offset"
                    class="transition-[stroke-dashoffset] duration-500 ease-out"
                />
            </svg>
            <span
                class="absolute inset-0 flex items-center justify-center text-[10px] font-bold tabular-nums text-deep"
            >
                {{ completion }}%
            </span>
        </button>

        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="open"
                    class="fixed inset-0 z-[80] flex items-end justify-center bg-ink/40 p-4 backdrop-blur-[2px] sm:items-center"
                    @click.self="dismiss"
                >
                    <Transition
                        appear
                        enter-active-class="transition duration-350 ease-[cubic-bezier(0.22,1,0.36,1)]"
                        enter-from-class="opacity-0 translate-y-4 sm:translate-y-2 sm:scale-[0.98]"
                        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-active-class="transition duration-200 ease-in"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0 translate-y-2"
                    >
                        <div
                            v-if="open"
                            class="w-full max-w-md overflow-hidden rounded-[1.5rem] bg-white shadow-premium-hover ring-1 ring-ink/[0.08]"
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="completion-title"
                        >
                            <div
                                class="relative overflow-hidden bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-5 text-white"
                            >
                                <div
                                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.14),transparent_55%)]"
                                    aria-hidden="true"
                                />
                                <div class="relative flex items-start justify-between gap-3">
                                    <div>
                                        <p
                                            class="text-[11px] font-semibold uppercase tracking-[0.14em] text-white/55"
                                        >
                                            Profile strength
                                        </p>
                                        <h2
                                            id="completion-title"
                                            class="mt-1 font-editorial text-xl font-semibold tracking-tight"
                                        >
                                            {{ completion }}% complete
                                        </h2>
                                        <p class="mt-1.5 text-sm font-medium text-white/65">
                                            A few touches make your page more convincing to clients.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        class="tap-target flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-white/70 ring-1 ring-white/15 transition hover:bg-white/16 hover:text-white"
                                        aria-label="Dismiss"
                                        @click="dismiss"
                                    >
                                        <i class="ti ti-x" aria-hidden="true" />
                                    </button>
                                </div>
                            </div>

                            <ul class="divide-y divide-ink/[0.05] px-2 py-2">
                                <li v-for="item in checklist" :key="item.key">
                                    <Link
                                        :href="item.href"
                                        class="tap-target flex items-start gap-3 rounded-xl px-3 py-3 transition-colors hover:bg-pale"
                                        @click="open = false"
                                    >
                                        <span
                                            class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-tint text-base text-deep"
                                        >
                                            <i :class="iconFor(item.key)" aria-hidden="true" />
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-bold text-ink">{{
                                                item.label
                                            }}</span>
                                            <span
                                                class="mt-0.5 block text-xs font-medium leading-relaxed text-ink/50"
                                            >
                                                {{ item.detail }}
                                            </span>
                                        </span>
                                        <i
                                            class="ti ti-chevron-right mt-2 text-ink/30"
                                            aria-hidden="true"
                                        />
                                    </Link>
                                </li>
                            </ul>

                            <div class="border-t border-ink/[0.05] px-4 py-3">
                                <button
                                    type="button"
                                    class="tap-target w-full rounded-xl py-2.5 text-sm font-semibold text-ink/45 transition-colors hover:bg-pale hover:text-ink"
                                    @click="dismiss"
                                >
                                    Remind me later
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const page = usePage();
const open = ref(false);

const completion = computed(() =>
    Math.max(0, Math.min(100, Number(page.props.auth?.user?.profile_completion) || 0)),
);

const checklist = computed(() => page.props.auth?.user?.completion_checklist || []);

const showRing = computed(() => completion.value < 100 && checklist.value.length > 0);

const ring = computed(() => {
    const radius = 15.5;
    const circumference = 2 * Math.PI * radius;
    const pct = completion.value;
    return {
        circumference,
        offset: circumference - (pct / 100) * circumference,
    };
});

const STORAGE_DISMISSED = 'isabi:checklist-dismissed';
const STORAGE_AUTO = 'isabi:checklist-auto-shown';

const iconFor = (key) => {
    const map = {
        photo: 'ti ti-camera',
        bio: 'ti ti-quote',
        area: 'ti ti-map-pin',
        whatsapp: 'ti ti-brand-whatsapp',
    };
    return map[key] || 'ti ti-circle-dashed';
};

const dismiss = () => {
    open.value = false;
    try {
        localStorage.setItem(STORAGE_DISMISSED, '1');
    } catch {
        // ignore
    }
};

onMounted(() => {
    if (!showRing.value) return;
    try {
        const dismissed = localStorage.getItem(STORAGE_DISMISSED);
        const autoShown = localStorage.getItem(STORAGE_AUTO);
        if (completion.value < 80 && !dismissed && !autoShown) {
            open.value = true;
            localStorage.setItem(STORAGE_AUTO, '1');
        }
    } catch {
        // ignore
    }
});

watch(completion, (value) => {
    if (value >= 100) {
        open.value = false;
        try {
            localStorage.removeItem(STORAGE_DISMISSED);
            localStorage.removeItem(STORAGE_AUTO);
        } catch {
            // ignore
        }
    }
});
</script>
