<template>
    <Head title="Support chat" />

    <AuthenticatedLayout :full-bleed="true">
        <div class="flex min-h-[calc(100dvh-4.25rem-5rem)] flex-col bg-pale md:min-h-[calc(100dvh-4.25rem)]">
            <header
                class="sticky top-[4.25rem] z-30 border-b border-ink/10 bg-white/95 backdrop-blur-xl"
            >
                <div class="mx-auto flex max-w-3xl items-center gap-3 px-4 py-3 sm:px-6">
                    <Link
                        :href="route('help.index')"
                        class="tap-target flex h-10 w-10 items-center justify-center rounded-xl text-ink/45 transition-colors hover:bg-pale hover:text-ink"
                        aria-label="Back to help"
                    >
                        <i class="ti ti-arrow-left text-lg" aria-hidden="true" />
                    </Link>
                    <div class="relative shrink-0">
                        <span
                            class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] text-white shadow-sm ring-1 ring-ink/5"
                        >
                            <i class="ti ti-headset text-lg" aria-hidden="true" />
                        </span>
                        <span
                            class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full bg-emerald-500 ring-2 ring-white"
                            title="Online"
                        />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold tracking-tight text-ink">
                            Isabi Support
                        </p>
                        <p class="truncate text-xs font-medium text-ink/45">
                            Usually replies within a few hours
                        </p>
                    </div>
                </div>
            </header>

            <div
                ref="scroller"
                class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-4 overflow-y-auto px-4 py-6 sm:px-6"
            >
                <p class="text-center text-[11px] font-semibold uppercase tracking-[0.14em] text-ink/35">
                    Today
                </p>

                <div
                    v-for="message in thread"
                    :key="message.id"
                    class="flex"
                    :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                >
                    <div
                        class="chat-bubble max-w-[85%] rounded-[1.25rem] px-4 py-3 text-sm font-medium leading-relaxed sm:max-w-[75%]"
                        :class="
                            message.role === 'user'
                                ? 'rounded-br-md bg-gradient-to-br from-[#1A4FB5] to-[#123B72] text-white shadow-[0_10px_24px_-12px_rgba(26,79,181,0.55)]'
                                : 'rounded-bl-md bg-white text-ink shadow-premium ring-1 ring-ink/[0.06]'
                        "
                    >
                        <p class="whitespace-pre-wrap">{{ message.body }}</p>
                        <p
                            class="mt-1.5 text-[10px] font-semibold tabular-nums"
                            :class="message.role === 'user' ? 'text-white/50' : 'text-ink/35'"
                        >
                            {{ message.time }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="border-t border-ink/10 bg-white/95 px-3 py-3 backdrop-blur-xl sm:px-4"
                style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))"
            >
                <form
                    class="mx-auto flex max-w-3xl items-end gap-2"
                    @submit.prevent="sendMessage"
                >
                    <div
                        class="flex min-h-12 flex-1 items-end rounded-2xl bg-pale ring-1 ring-ink/[0.06] focus-within:ring-2 focus-within:ring-base/20"
                    >
                        <textarea
                            v-model="form.body"
                            rows="1"
                            placeholder="Type your message…"
                            class="max-h-32 min-h-12 w-full resize-none bg-transparent px-4 py-3 text-sm font-medium text-ink outline-none placeholder:text-ink/35"
                            @keydown.enter.exact.prevent="sendMessage"
                        />
                    </div>
                    <button
                        type="submit"
                        class="tap-target flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-base-action text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover disabled:opacity-40"
                        :disabled="!form.body.trim() || form.processing"
                        aria-label="Send message"
                    >
                        <i class="ti ti-send text-lg" aria-hidden="true" />
                    </button>
                </form>
                <p class="mx-auto mt-2 max-w-3xl text-center text-[11px] font-medium text-ink/35">
                    Replies from the Isabi team show up in this thread.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

const props = defineProps({
    messages: { type: Array, default: () => [] },
});

const page = usePage();
const user = page.props.auth?.user;
const firstName = String(user?.first_name || user?.name || 'there').split(' ')[0];

const scroller = ref(null);
const form = useForm({ body: '' });

const welcome = {
    id: 'welcome',
    role: 'support',
    body: `Hi ${firstName} — welcome to Isabi Support.\n\nWe’re here for anything about your page, tokens, reviews, or account. Tell us what’s going on and we’ll help you sort it.`,
    time: '',
};

const thread = computed(() => (props.messages.length ? props.messages : [welcome]));

const scrollToBottom = async () => {
    await nextTick();
    if (scroller.value) {
        scroller.value.scrollTop = scroller.value.scrollHeight;
    }
};

const sendMessage = () => {
    if (!form.body.trim()) return;

    form.post(route('help.chat.send'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('body');
            scrollToBottom();
        },
    });
};

watch(() => props.messages.length, scrollToBottom);
onMounted(scrollToBottom);
</script>

<style scoped>
.chat-bubble {
    animation: bubble-in 0.35s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes bubble-in {
    from {
        opacity: 0;
        transform: translateY(8px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
</style>
