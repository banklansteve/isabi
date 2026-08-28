<template>
    <Head title="Support chat" />

    <AuthenticatedLayout :full-bleed="true" :flush="true">
        <div class="support-shell relative flex h-[calc(100dvh-4.25rem)] flex-col overflow-hidden">
            <div class="support-glow pointer-events-none absolute inset-0" aria-hidden="true" />

            <div class="relative z-[1] flex min-h-0 flex-1 flex-col">
                <div class="mx-auto flex w-full max-w-3xl shrink-0 items-center gap-3 px-4 pb-2 pt-3 sm:px-6 sm:pt-4">
                    <Link
                        :href="route('help.index')"
                        class="tap-target flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-ink/45 transition-colors hover:bg-ink/[0.04] hover:text-ink"
                        aria-label="Back to help"
                    >
                        <i class="ti ti-arrow-left text-lg" aria-hidden="true" />
                    </Link>
                    <div class="relative shrink-0">
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-[#1A4FB5] via-[#154296] to-[#0B1F3A] text-white shadow-[0_10px_24px_-12px_rgba(26,79,181,0.55)]"
                        >
                            <i class="ti ti-headset text-[1.05rem]" aria-hidden="true" />
                        </span>
                        <span
                            class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full ring-2 ring-[#F4F6FA]"
                            :class="customerStateDot(chat.state)"
                        />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[15px] font-bold tracking-tight text-ink">Isabi Support</p>
                        <p class="truncate text-[12px] font-medium text-ink/45">{{ headerCopy }}</p>
                    </div>
                </div>

                <p
                    v-if="chat.state === 'offline' || chat.state === 'next_agent'"
                    class="mx-auto w-full max-w-3xl shrink-0 px-4 pb-2 text-[12px] font-medium leading-relaxed text-ink/50 sm:px-6"
                >
                    {{ chat.state === 'next_agent' ? chat.state_label : chat.offline_copy }}
                </p>

                <div
                    ref="scroller"
                    class="mx-auto flex min-h-0 w-full max-w-3xl flex-1 flex-col gap-3.5 overflow-y-auto overscroll-contain px-4 pb-4 pt-1 sm:gap-4 sm:px-6"
                >
                    <p class="py-1 text-center text-[10px] font-bold uppercase tracking-[0.16em] text-ink/30">
                        {{ chat.state_label }}
                    </p>

                    <div v-if="showWelcome" class="flex justify-start">
                        <div
                            class="chat-bubble max-w-[88%] rounded-[1.35rem] rounded-bl-md bg-white/80 px-4 py-3.5 text-sm font-medium leading-relaxed text-ink shadow-[0_8px_30px_-18px_rgba(7,20,39,0.35)] ring-1 ring-ink/[0.05] backdrop-blur-sm sm:max-w-[78%]"
                        >
                            <p class="whitespace-pre-wrap">{{ welcomeBody }}</p>
                        </div>
                    </div>

                    <div
                        v-if="showStarters"
                        class="flex flex-wrap gap-2 pt-0.5"
                        role="list"
                        aria-label="Conversation starters"
                    >
                        <button
                            v-for="starter in chat.starters"
                            :key="starter.key"
                            type="button"
                            class="tap-target min-h-10 rounded-full bg-white/75 px-4 py-2 text-left text-[13px] font-semibold text-ink ring-1 ring-ink/[0.07] backdrop-blur-sm transition-colors hover:bg-white hover:text-deep"
                            :disabled="sending"
                            @click="useStarter(starter)"
                        >
                            {{ starter.label }}
                        </button>
                    </div>

                    <div
                        v-for="(message, index) in chat.messages"
                        :key="message.id"
                        class="flex"
                        :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                    >
                        <ChatBubbleReactions
                            :reactions="message.reactions || []"
                            :align="message.role === 'user' ? 'end' : 'start'"
                            :endpoint="route('help.chat.react', message.id)"
                            @updated="(reactions) => (message.reactions = reactions)"
                        >
                            <div
                                class="chat-bubble max-w-[88%] rounded-[1.35rem] px-4 py-3 text-sm font-medium leading-relaxed sm:max-w-[78%]"
                                :class="
                                    message.role === 'user'
                                        ? 'rounded-br-md bg-gradient-to-br from-[#1A4FB5] to-[#123B72] text-white shadow-[0_12px_28px_-14px_rgba(26,79,181,0.6)]'
                                        : 'rounded-bl-md bg-white/85 text-ink shadow-[0_8px_30px_-18px_rgba(7,20,39,0.35)] ring-1 ring-ink/[0.05] backdrop-blur-sm'
                                "
                            >
                                <a
                                    v-if="message.attachment?.image"
                                    :href="message.attachment.url"
                                    target="_blank"
                                    rel="noopener"
                                    class="mb-2 block overflow-hidden rounded-xl"
                                >
                                    <img
                                        :src="message.attachment.url"
                                        :alt="message.attachment.name"
                                        class="max-h-52 w-full object-cover"
                                        @load="scrollToBottom"
                                    />
                                </a>
                                <a
                                    v-else-if="message.attachment"
                                    :href="message.attachment.url"
                                    target="_blank"
                                    rel="noopener"
                                    class="mb-2 inline-flex items-center gap-2 rounded-xl bg-black/10 px-3 py-2 text-xs font-semibold"
                                >
                                    <i class="ti ti-paperclip" aria-hidden="true" />
                                    {{ message.attachment.name }}
                                </a>
                                <p v-if="message.body" class="whitespace-pre-wrap">{{ message.body }}</p>
                                <p
                                    class="mt-1.5 text-[10px] font-semibold tabular-nums"
                                    :class="message.role === 'user' ? 'text-white/50' : 'text-ink/35'"
                                >
                                    {{ message.time }}
                                    <span
                                        v-if="message.role === 'user' && isLatestOwn(index) && chat.seen"
                                        class="ms-1 font-medium"
                                    >
                                        · Seen
                                    </span>
                                </p>
                            </div>
                        </ChatBubbleReactions>
                    </div>

                    <div v-if="chat.typing" class="flex justify-start">
                        <div
                            class="rounded-[1.35rem] rounded-bl-md bg-white/85 px-4 py-3 text-ink/45 shadow-[0_8px_30px_-18px_rgba(7,20,39,0.35)] ring-1 ring-ink/[0.05] backdrop-blur-sm"
                        >
                            <SupportTypingDots label="Support is typing…" />
                        </div>
                    </div>

                    <div ref="threadEnd" class="h-px w-full shrink-0" aria-hidden="true" />

                    <div
                        v-if="chat.csat?.prompt"
                        class="rounded-[1.35rem] bg-white/90 px-4 py-4 shadow-[0_8px_30px_-18px_rgba(7,20,39,0.35)] ring-1 ring-ink/[0.05] backdrop-blur-sm"
                    >
                        <p class="text-sm font-bold text-ink">How did we do?</p>
                        <p class="mt-1 text-[13px] font-medium text-ink/45">Optional — skip anytime.</p>
                        <div class="mt-3 flex gap-2" role="group" aria-label="Satisfaction rating">
                            <button
                                v-for="n in 5"
                                :key="n"
                                type="button"
                                class="tap-target flex h-11 w-11 items-center justify-center rounded-xl bg-pale text-sm font-bold text-ink ring-1 ring-ink/[0.06] hover:bg-tint"
                                :aria-label="`${n} out of 5`"
                                :disabled="sending"
                                @click="submitCsat(n)"
                            >
                                {{ n }}
                            </button>
                        </div>
                        <button
                            type="button"
                            class="mt-3 text-[12px] font-semibold text-ink/40 hover:text-ink"
                            @click="dismissCsat"
                        >
                            Skip
                        </button>
                    </div>
                </div>

                <div
                    class="relative z-[1] shrink-0 px-3 pb-3 pt-1 sm:px-4"
                    style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))"
                >
                    <div class="mx-auto max-w-3xl">
                        <p v-if="uploadError" class="mb-2 text-[12px] font-semibold text-coral-deep">
                            {{ uploadError }}
                            <button type="button" class="ms-2 underline" @click="retryUpload">Retry</button>
                        </p>
                        <div v-if="uploadProgress !== null" class="mb-2">
                            <div class="h-1 overflow-hidden rounded-full bg-ink/[0.06]">
                                <div
                                    class="h-full bg-base-action transition-[width]"
                                    :style="{ width: `${uploadProgress}%` }"
                                />
                            </div>
                        </div>

                        <form
                            class="flex items-end gap-2 rounded-[1.5rem] bg-white/90 p-2 shadow-[0_16px_40px_-24px_rgba(7,20,39,0.45)] ring-1 ring-ink/[0.06] backdrop-blur-xl"
                            @submit.prevent="sendMessage()"
                        >
                            <div class="flex shrink-0 items-center gap-0.5 self-end pb-0.5">
                                <label
                                    class="tap-target flex h-11 w-11 cursor-pointer items-center justify-center rounded-2xl text-ink/40 transition-colors hover:bg-pale hover:text-ink"
                                >
                                    <i class="ti ti-paperclip text-lg" aria-hidden="true" />
                                    <span class="sr-only">Attach a file</span>
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        class="hidden"
                                        accept="image/jpeg,image/png,image/webp,image/gif,application/pdf"
                                        @change="onFile"
                                    />
                                </label>
                                <ChatEmojiPicker @pick="appendEmoji" />
                            </div>
                            <textarea
                                ref="composer"
                                v-model="draft"
                                rows="1"
                                placeholder="Type your message…"
                                class="max-h-32 min-h-11 w-full flex-1 resize-none bg-transparent px-1 py-2.5 text-sm font-medium text-ink outline-none placeholder:text-ink/35"
                                @keydown.enter.exact.prevent="sendMessage()"
                                @input="onType"
                            />
                            <button
                                type="submit"
                                class="tap-target flex h-11 w-11 shrink-0 items-center justify-center self-end rounded-2xl bg-base-action text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover disabled:opacity-40"
                                :disabled="!canSend || sending"
                                aria-label="Send message"
                            >
                                <i class="ti ti-send text-lg" aria-hidden="true" />
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import SupportTypingDots from '@/Components/App/SupportTypingDots.vue';
import ChatBubbleReactions from '@/Components/Chat/ChatBubbleReactions.vue';
import ChatEmojiPicker from '@/Components/Chat/ChatEmojiPicker.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { customerStateDot } from '@/utils/supportChat';
import { scrollChatToEnd } from '@/utils/chatScroll';
import { echoClient, echoConnected } from '@/echo';
import { Head, Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    conversation: { type: Object, required: true },
});

const chat = ref({ ...props.conversation });
const draft = ref('');
const sending = ref(false);
const uploadProgress = ref(null);
const uploadError = ref('');
const pendingFile = ref(null);
const scroller = ref(null);
const threadEnd = ref(null);
const composer = ref(null);
const fileInput = ref(null);
let pollTimer = null;
let typingTimer = null;
let typingHideTimer = null;
let echoReady = false;

const headerCopy = computed(() => {
    if (chat.value.agent_first_name && chat.value.state === 'connected') {
        return `${chat.value.agent_first_name} is with you`;
    }

    return chat.value.availability_copy || chat.value.state_label;
});

const showStarters = computed(() => !chat.value.messages?.length);
const showWelcome = computed(() => showStarters.value);
const welcomeBody = computed(
    () => `Hi ${chat.value.customer_name || 'there'} — welcome to Isabi Support.\n\nPick a topic below or type your own message. We’ll pick this up even if the team is offline.`,
);
const canSend = computed(() => Boolean(draft.value.trim() || pendingFile.value));
const lastMessageId = computed(() => chat.value.messages?.at(-1)?.id ?? null);

const isLatestOwn = (index) => {
    const messages = chat.value.messages || [];
    const last = [...messages].reverse().find((item) => item.role === 'user');

    return last && messages[index]?.id === last.id;
};

const scrollToBottom = async () => {
    await nextTick();
    scrollChatToEnd(scroller, threadEnd);
};

const apply = (payload) => {
    chat.value = payload;
    uploadError.value = '';
    scrollToBottom();
};

const sendMessage = async (override = {}) => {
    const body = override.body ?? draft.value.trim();
    const file = override.file ?? pendingFile.value;
    const topicKey = override.topic_key ?? null;

    if (!body && !file) {
        return;
    }

    sending.value = true;
    uploadError.value = '';
    uploadProgress.value = file ? 0 : null;

    const data = new FormData();
    if (body) data.append('body', body);
    if (topicKey) data.append('topic_key', topicKey);
    if (file) data.append('attachment', file);

    try {
        const { data: payload } = await axios.post(route('help.chat.send'), data, {
            headers: { Accept: 'application/json' },
            onUploadProgress: (event) => {
                if (!event.total) return;
                uploadProgress.value = Math.round((event.loaded / event.total) * 100);
            },
        });
        draft.value = '';
        pendingFile.value = null;
        if (fileInput.value) fileInput.value.value = '';
        apply(payload);
    } catch (error) {
        if (error?.response?.status === 429) {
            uploadError.value = 'Please wait a moment before sending another message.';
        } else if (file) {
            uploadError.value = 'That file didn’t upload. Try again.';
        } else {
            uploadError.value = error?.response?.data?.message || 'Could not send. Try again.';
        }
    } finally {
        sending.value = false;
        uploadProgress.value = null;
    }
};

const retryUpload = () => sendMessage();

const useStarter = (starter) => {
    if (starter.message) {
        sendMessage({ body: starter.message, topic_key: starter.key });
        return;
    }

    composer.value?.focus();
};

const appendEmoji = (emoji) => {
    draft.value = `${draft.value || ''}${emoji}`;
    composer.value?.focus();
};

const onFile = (event) => {
    const file = event.target.files?.[0];
    pendingFile.value = file || null;
};

const onType = () => {
    window.clearTimeout(typingTimer);
    typingTimer = window.setTimeout(() => {
        axios.post(route('help.chat.typing'), {}, { headers: { Accept: 'application/json' } }).catch(() => {});
    }, 280);
};

const sync = async () => {
    try {
        const { data } = await axios.get(route('help.chat.sync'), { headers: { Accept: 'application/json' } });
        const prevId = lastMessageId.value;
        const wasTyping = chat.value.typing;
        chat.value = data;
        if (data.messages?.at(-1)?.id !== prevId || data.typing !== wasTyping) {
            scrollToBottom();
        }
    } catch {
        // Keep the open thread if a poll fails.
    }
};

const submitCsat = async (score) => {
    const { data } = await axios.post(route('help.chat.csat'), { score }, { headers: { Accept: 'application/json' } });
    apply(data);
};

const dismissCsat = async () => {
    const { data } = await axios.post(route('help.chat.csat'), { dismiss: true }, { headers: { Accept: 'application/json' } });
    apply(data);
};

const onCustomerEvent = (event) => {
    const payload = event.detail?.conversation;
    if (!payload) {
        return;
    }
    const prevId = lastMessageId.value;
    chat.value = payload;
    if (payload.messages?.at(-1)?.id !== prevId) {
        scrollToBottom();
    }
};

const onTypingEvent = (event) => {
    const payload = event.detail;
    if (payload?.side !== 'staff') {
        return;
    }
    if (payload.ticket_id && chat.value.id && payload.ticket_id !== chat.value.id) {
        return;
    }
    chat.value = { ...chat.value, typing: true };
    scrollToBottom();
    window.clearTimeout(typingHideTimer);
    typingHideTimer = window.setTimeout(() => {
        chat.value = { ...chat.value, typing: false };
    }, 4000);
};

const bindEcho = () => {
    const echo = echoClient(usePage().props.reverb);
    if (!echo?.connector?.pusher?.connection) {
        return;
    }
    const connection = echo.connector.pusher.connection;
    const mark = (state) => {
        echoReady = state === 'connected';
    };
    mark(connection.state);
    connection.bind('connected', () => mark('connected'));
    connection.bind('disconnected', () => mark('disconnected'));
    connection.bind('unavailable', () => mark('unavailable'));
};

watch(
    () => props.conversation,
    (value) => {
        chat.value = value;
        scrollToBottom();
    },
    { deep: true },
);

watch(lastMessageId, (id, prev) => {
    if (id && id !== prev) {
        scrollToBottom();
    }
});

watch(
    () => chat.value.typing,
    (typing) => {
        if (typing) {
            scrollToBottom();
        }
    },
);

onMounted(() => {
    scrollToBottom();
    bindEcho();
    window.addEventListener('isabi:support-customer', onCustomerEvent);
    window.addEventListener('isabi:support-typing', onTypingEvent);
    const ms = Number(chat.value.poll_ms || 8000);
    pollTimer = window.setInterval(() => {
        if (!echoReady && !echoConnected()) {
            sync();
        }
    }, ms);
});

onUnmounted(() => {
    window.clearInterval(pollTimer);
    window.clearTimeout(typingTimer);
    window.clearTimeout(typingHideTimer);
    window.removeEventListener('isabi:support-customer', onCustomerEvent);
    window.removeEventListener('isabi:support-typing', onTypingEvent);
});
</script>

<style scoped>
.support-shell {
    background:
        radial-gradient(120% 80% at 50% -10%, rgba(26, 79, 181, 0.08), transparent 55%),
        radial-gradient(90% 60% at 100% 100%, rgba(26, 79, 181, 0.05), transparent 50%),
        #f4f6fa;
}

.support-glow {
    background-image: radial-gradient(rgba(7, 20, 39, 0.035) 0.7px, transparent 0.7px);
    background-size: 18px 18px;
    mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.55), transparent 88%);
}

.chat-bubble {
    animation: bubble-in 0.28s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes bubble-in {
    from {
        opacity: 0;
        transform: translateY(6px) scale(0.99);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (prefers-reduced-motion: reduce) {
    .chat-bubble {
        animation: none;
    }
}
</style>
