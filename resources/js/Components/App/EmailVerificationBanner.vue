<template>
    <div
        v-if="show"
        class="border-b border-base-action/15 bg-gradient-to-r from-[#E8F0FF] via-[#F4F7FC] to-[#E8F0FF]"
    >
        <div
            class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-10"
        >
            <div class="min-w-0 flex items-start gap-3">
                <span
                    class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-base-action/10 text-base-action"
                >
                    <i class="ti ti-mail-check text-lg" aria-hidden="true" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-ink">Verify your email to unlock full access</p>
                    <p class="mt-0.5 text-xs font-medium text-ink/55">
                        Enter the code we sent to
                        <span class="font-semibold text-ink/70">{{ email }}</span>
                        — logging jobs and review requests stay locked until you do.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:shrink-0">
                <Link
                    :href="route('verification.notice')"
                    class="tap-target inline-flex items-center justify-center rounded-xl bg-base-action px-3.5 py-2 text-xs font-bold text-white shadow-sm transition-colors hover:bg-base-hover"
                >
                    Enter code
                </Link>
                <button
                    type="button"
                    class="tap-target inline-flex items-center justify-center rounded-xl bg-white px-3.5 py-2 text-xs font-bold text-base-action ring-1 ring-base-action/20 transition-colors hover:bg-tint disabled:opacity-50"
                    :disabled="cooldown > 0 || sending"
                    @click="resend"
                >
                    {{ cooldown > 0 ? `Resend in ${cooldown}s` : sending ? 'Sending…' : 'Resend code' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const page = usePage();
const sending = ref(false);
const cooldown = ref(0);
let timer = null;

const user = computed(() => page.props.auth?.user);
const show = computed(
    () =>
        !!user.value &&
        !user.value.is_staff &&
        user.value.email_verified_at === null &&
        !route().current('register') &&
        !route().current('verification.notice'),
);
const email = computed(() => user.value?.email || '');

const resend = () => {
    if (cooldown.value > 0 || sending.value) return;
    sending.value = true;
    router.post(
        route('verification.send'),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                sending.value = false;
            },
            onSuccess: () => {
                cooldown.value = 45;
            },
        },
    );
};

onMounted(() => {
    timer = window.setInterval(() => {
        if (cooldown.value > 0) cooldown.value -= 1;
    }, 1000);
});

onBeforeUnmount(() => {
    if (timer) window.clearInterval(timer);
});
</script>
