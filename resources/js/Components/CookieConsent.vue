<template>
    <Teleport to="body">
        <Transition name="cookie-sheet">
            <div
                v-if="visible"
                class="pointer-events-none fixed inset-x-0 bottom-0 z-[80] flex justify-center px-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] sm:px-6 lg:px-8"
                role="dialog"
                aria-modal="false"
                aria-labelledby="cookie-consent-title"
                aria-describedby="cookie-consent-body"
            >
                <div
                    class="pointer-events-auto w-full max-w-4xl overflow-hidden rounded-2xl border border-ink/10 bg-white shadow-[0_-12px_48px_rgba(11,31,58,0.12)] lg:max-w-5xl xl:max-w-6xl"
                >
                    <div
                        class="h-1 w-full bg-gradient-to-r from-deep via-base to-coral"
                        aria-hidden="true"
                    />

                    <div class="bg-pale/30 px-4 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-6">
                        <div class="flex flex-col gap-4 sm:gap-5 lg:flex-row lg:items-center lg:gap-8">
                            <div class="flex min-w-0 flex-1 gap-3 sm:gap-4">
                                <span
                                    class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-tint text-base text-deep sm:h-10 sm:w-10 sm:text-lg"
                                >
                                    <i class="ti ti-cookie" aria-hidden="true" />
                                </span>

                                <div class="min-w-0">
                                    <h2
                                        id="cookie-consent-title"
                                        class="text-sm font-semibold tracking-tight text-neutral-700 sm:text-[0.95rem]"
                                    >
                                        Cookies on Isabi
                                    </h2>
                                    <p
                                        id="cookie-consent-body"
                                        class="mt-1 text-xs leading-relaxed text-neutral-600 sm:text-[0.8125rem] sm:leading-relaxed"
                                    >
                                        We use essential cookies to keep you signed in and keep the product secure.
                                        If you accept, we may also use analytics cookies to understand usage and improve Isabi.
                                        You can change your mind anytime.
                                        <Link
                                            :href="route('cookies')"
                                            class="font-medium text-neutral-700 underline underline-offset-2 transition-colors hover:text-neutral-900"
                                            @click="onPolicyClick"
                                        >
                                            Cookie policy
                                        </Link>
                                    </p>
                                </div>
                            </div>

                            <div class="flex w-full shrink-0 flex-col gap-2 sm:flex-row sm:items-center lg:w-auto">
                                <button
                                    type="button"
                                    class="tap-target order-2 inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-ink/10 bg-white px-5 text-xs font-semibold text-neutral-600 transition-colors duration-200 hover:bg-pale hover:text-neutral-800 sm:order-1 sm:text-sm lg:min-w-[8.5rem] lg:flex-none"
                                    :disabled="processing"
                                    @click="choose('rejected')"
                                >
                                    Reject
                                </button>
                                <button
                                    type="button"
                                    class="tap-target order-1 inline-flex min-h-11 flex-1 items-center justify-center rounded-xl bg-base-action px-5 text-xs font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-colors duration-200 hover:bg-base-hover sm:order-2 sm:text-sm lg:min-w-[8.5rem] lg:flex-none"
                                    :disabled="processing"
                                    @click="choose('accepted')"
                                >
                                    {{ processing ? 'Saving…' : 'Accept cookies' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const page = usePage();
const processing = ref(false);
const forced = ref(false);
const dismissedLocally = ref(false);

const decided = computed(() => !!page.props.cookieConsent?.decided);

const visible = computed(() => {
    if (dismissedLocally.value && !forced.value) {
        return false;
    }
    return forced.value || !decided.value;
});

const choose = (status) => {
    processing.value = true;
    router.post(
        route('cookie-consent.store'),
        { status },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                processing.value = false;
                forced.value = false;
                dismissedLocally.value = true;
            },
        },
    );
};

const openPreferences = () => {
    dismissedLocally.value = false;
    forced.value = true;
};

const onPolicyClick = () => {
    if (decided.value) {
        forced.value = false;
    }
};

onMounted(() => {
    window.addEventListener('isabi:open-cookie-consent', openPreferences);
});

onUnmounted(() => {
    window.removeEventListener('isabi:open-cookie-consent', openPreferences);
});
</script>

<style scoped>
.cookie-sheet-enter-active {
    transition:
        opacity 0.35s cubic-bezier(0.22, 1, 0.36, 1),
        transform 0.4s cubic-bezier(0.22, 1, 0.36, 1);
}

.cookie-sheet-leave-active {
    transition:
        opacity 0.25s ease,
        transform 0.3s cubic-bezier(0.4, 0, 1, 1);
}

.cookie-sheet-enter-from,
.cookie-sheet-leave-to {
    opacity: 0;
    transform: translateY(110%);
}
</style>
