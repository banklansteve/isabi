<template>
    <Head :title="conversation ? conversation.title : 'ASAP'" />

    <AdminChrome title="ASAP" eyebrow="Staff channel" />

    <div class="flex h-[calc(100dvh-11rem)] min-h-[22rem] overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05] lg:h-[calc(100dvh-10rem)]">
        <aside
            class="flex w-full flex-col border-ink/[0.06] lg:w-[22rem] lg:shrink-0 lg:border-r"
            :class="conversation ? 'hidden lg:flex' : 'flex'"
        >
            <div class="space-y-2 border-b border-ink/[0.06] p-3">
                <input
                    v-model="query"
                    type="search"
                    placeholder="Search ASAP & DMs…"
                    class="w-full rounded-xl border border-ink/10 bg-[#F4F6FA] px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                />
                <button
                    type="button"
                    class="tap-target flex w-full items-center justify-center gap-2 rounded-xl bg-base-action px-3 py-2.5 text-[13px] font-semibold text-white hover:bg-base-hover"
                    @click="directoryOpen = !directoryOpen"
                >
                    <i class="ti ti-message-plus text-base" aria-hidden="true" />
                    Message a teammate
                </button>
                <div v-if="directoryOpen" class="max-h-48 overflow-y-auto rounded-xl ring-1 ring-ink/[0.08]">
                    <button
                        v-for="peer in filteredDirectory"
                        :key="peer.id"
                        type="button"
                        class="flex w-full items-center gap-3 px-3 py-2.5 text-left hover:bg-pale"
                        @click="startDirect(peer.id)"
                    >
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-tint text-[11px] font-bold text-deep">
                            <img v-if="peer.avatar_url" :src="peer.avatar_url" alt="" class="h-full w-full object-cover" />
                            <span v-else>{{ initials(peer.name) }}</span>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[13px] font-bold text-ink">{{ peer.name }}</span>
                            <span class="block truncate text-[11px] font-medium text-ink/40">{{ peer.role_label }}</span>
                        </span>
                    </button>
                    <p v-if="!filteredDirectory.length" class="px-3 py-3 text-[12px] font-medium text-ink/40">
                        No teammates match.
                    </p>
                </div>
            </div>

            <div v-if="loading && !visibleList.length" class="space-y-2 p-3">
                <div v-for="n in 5" :key="n" class="h-16 animate-pulse rounded-xl bg-pale" />
            </div>
            <AdminEmpty
                v-else-if="!visibleList.length"
                title="No conversations yet"
                description="ASAP is the shared ops channel. Start a DM with any teammate."
                icon="ti ti-bolt"
            />
            <ul v-else class="min-h-0 flex-1 overflow-y-auto">
                <li v-for="item in visibleList" :key="item.uid">
                    <Link
                        :href="item.href || route('admin.asap.show', item.uid)"
                        prefetch
                        class="flex gap-3 border-b border-ink/[0.05] px-3.5 py-3 transition-colors hover:bg-pale/80"
                        :class="conversation?.uid === item.uid ? 'bg-tint/70' : ''"
                    >
                        <span class="relative mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-tint text-[11px] font-bold text-deep">
                            <img
                                v-if="item.peer?.avatar_url"
                                :src="item.peer.avatar_url"
                                alt=""
                                class="h-full w-full object-cover"
                            />
                            <i v-else-if="item.type === 'asap'" :class="item.icon" class="text-lg text-base-action" aria-hidden="true" />
                            <span v-else>{{ item.peer?.initials || initials(item.title) }}</span>
                            <span
                                v-if="item.unread"
                                class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full bg-base-action ring-2 ring-white"
                            />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-2">
                                <span
                                    class="truncate text-[13px] text-ink"
                                    :class="item.unread ? 'font-extrabold' : 'font-bold'"
                                >
                                    {{ item.title }}
                                </span>
                                <span v-if="item.type === 'asap'" class="shrink-0 rounded-full bg-tint px-1.5 py-0.5 text-[10px] font-bold text-deep">
                                    Group
                                </span>
                            </span>
                            <span
                                class="mt-0.5 block truncate text-[12px]"
                                :class="item.unread ? 'font-bold text-ink' : 'font-medium text-ink/45'"
                            >
                                {{ item.subtitle }}
                            </span>
                            <span class="mt-1 block text-[11px] font-bold text-ink/35">{{ item.when || '—' }}</span>
                        </span>
                    </Link>
                </li>
            </ul>
        </aside>

        <section
            class="min-w-0 flex-1 flex-col bg-[#F4F6FA]"
            :class="conversation ? 'flex' : 'hidden lg:flex'"
        >
            <div v-if="!conversation" class="flex flex-1 flex-col items-center justify-center px-6 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-base-action shadow-premium">
                    <i class="ti ti-bolt text-2xl" aria-hidden="true" />
                </span>
                <p class="mt-4 text-sm font-bold text-ink">Pick ASAP or a teammate</p>
                <p class="mt-1 max-w-sm text-[13px] font-medium text-ink/45">
                    Operations and Super Admin share ASAP. Direct messages stay between two people.
                </p>
            </div>

            <template v-else>
                <header class="flex items-center gap-3 border-b border-ink/[0.06] bg-white px-3 py-3 sm:px-4">
                    <Link
                        :href="route('admin.asap.index')"
                        class="tap-target flex h-10 w-10 items-center justify-center rounded-xl text-ink/40 hover:bg-pale lg:hidden"
                        aria-label="Back to inbox"
                    >
                        <i class="ti ti-arrow-left text-lg" aria-hidden="true" />
                    </Link>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-tint text-[11px] font-bold text-deep">
                        <img
                            v-if="conversation.peer?.avatar_url"
                            :src="conversation.peer.avatar_url"
                            alt=""
                            class="h-full w-full object-cover"
                        />
                        <i v-else-if="conversation.type === 'asap'" class="ti ti-bolt text-lg text-base-action" aria-hidden="true" />
                        <span v-else>{{ conversation.peer?.initials || initials(conversation.title) }}</span>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-ink">{{ conversation.title }}</p>
                        <p class="truncate text-[12px] font-medium text-ink/40">
                            <template v-if="conversation.type === 'asap'">
                                All operations &amp; Super Admin
                            </template>
                            <template v-else>
                                {{ conversation.peer?.name || 'Direct message' }}
                            </template>
                        </p>
                    </div>
                </header>

                <div
                    ref="scroller"
                    class="min-h-0 flex-1 space-y-3 overflow-y-auto px-3 py-4 sm:px-5"
                    @scroll="queueMarkRead"
                >
                    <div
                        v-for="message in conversation.messages"
                        :key="message.id"
                        class="flex w-full"
                    >
                        <div
                            class="min-w-0 max-w-[85%]"
                            :class="message.mine ? 'ms-auto' : 'me-auto'"
                        >
                            <ChatBubbleReactions
                                :reactions="message.reactions || []"
                                :align="message.mine ? 'end' : 'start'"
                                :endpoint="route('admin.asap.react', [conversation.uid, message.id])"
                                @updated="(reactions) => (message.reactions = reactions)"
                            >
                                <div
                                    class="w-full rounded-2xl px-4 py-3 text-sm font-medium leading-relaxed"
                                    :class="
                                        message.mine
                                            ? 'rounded-br-md bg-base-action text-white'
                                            : 'rounded-bl-md bg-white text-ink ring-1 ring-ink/[0.06]'
                                    "
                                >
                                <p
                                    v-if="!message.mine && conversation.type === 'asap'"
                                    class="mb-1 text-[11px] font-bold text-base-action"
                                >
                                    {{ message.user?.name }}
                                </p>
                                <a
                                    v-if="message.attachment?.gif || message.attachment?.image"
                                    :href="message.attachment.url"
                                    target="_blank"
                                    rel="noopener"
                                    class="mb-2 block overflow-hidden rounded-xl"
                                >
                                    <img
                                        :src="message.attachment.url"
                                        :alt="message.attachment.name"
                                        class="max-h-48 w-full"
                                        :class="message.attachment?.gif ? 'object-contain' : 'object-cover'"
                                        @load="scrollThread"
                                    />
                                </a>
                                <a
                                    v-else-if="message.attachment?.url"
                                    :href="message.attachment.url"
                                    target="_blank"
                                    rel="noopener"
                                    class="mb-2 inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-[12px] font-semibold"
                                    :class="message.mine ? 'bg-white/15 text-white' : 'bg-pale text-ink'"
                                >
                                    <i class="ti ti-paperclip" aria-hidden="true" />
                                    {{ message.attachment.name || 'Attachment' }}
                                </a>
                                <p v-if="message.body" class="whitespace-pre-wrap">{{ message.body }}</p>
                                <p
                                    class="mt-1.5 text-[10px] font-semibold"
                                    :class="message.mine ? 'text-white/55' : 'text-ink/35'"
                                >
                                    {{ message.when }}
                                </p>
                                </div>
                            </ChatBubbleReactions>
                        </div>
                    </div>
                    <div v-if="typingLabel" class="flex justify-start">
                        <div class="rounded-2xl bg-white px-4 py-2.5 text-ink/40 ring-1 ring-ink/[0.06]">
                            <SupportTypingDots :label="typingLabel" />
                        </div>
                    </div>
                    <div ref="threadEnd" class="h-px w-full shrink-0" aria-hidden="true" />
                </div>

                <form
                    class="border-t border-ink/[0.06] bg-white p-3"
                    @submit.prevent="send"
                    @click="queueMarkRead"
                >
                    <textarea
                        ref="composer"
                        v-model="draft"
                        rows="2"
                        placeholder="Message ASAP…"
                        class="w-full resize-none rounded-xl border border-ink/10 px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                        @keydown.enter.exact.prevent="send"
                        @input="onDraft"
                        @focus="queueMarkRead"
                    />
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <label class="tap-target flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl bg-pale text-ink/45 hover:text-ink">
                            <i class="ti ti-paperclip" aria-hidden="true" />
                            <span class="sr-only">Attach</span>
                            <input
                                type="file"
                                class="hidden"
                                accept="image/jpeg,image/png,image/webp,image/gif,application/pdf"
                                @change="onFile"
                            />
                        </label>
                        <ChatEmojiPicker @pick="appendEmoji" />
                        <ChatGifPicker @pick="onGifPick" />
                        <span v-if="fileName" class="truncate text-[11px] font-medium text-ink/40">{{ fileName }}</span>
                        <span v-else-if="gif?.name" class="truncate text-[11px] font-medium text-ink/40">GIF · {{ gif.name }}</span>
                        <button
                            type="submit"
                            class="ms-auto tap-target rounded-xl bg-base-action px-4 py-2 text-[13px] font-semibold text-white hover:bg-base-hover disabled:opacity-40"
                            :disabled="sending || (!draft.trim() && !file && !gif)"
                        >
                            Send
                        </button>
                    </div>
                </form>
            </template>
        </section>
    </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import SupportTypingDots from '@/Components/App/SupportTypingDots.vue';
import ChatBubbleReactions from '@/Components/Chat/ChatBubbleReactions.vue';
import ChatEmojiPicker from '@/Components/Chat/ChatEmojiPicker.vue';
import ChatGifPicker from '@/Components/Chat/ChatGifPicker.vue';
import { echoClient } from '@/echo';
import { scrollChatToEnd } from '@/utils/chatScroll';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    conversations: { type: Array, default: () => [] },
    conversation: { type: Object, default: null },
    directory: { type: Array, default: () => [] },
    unread_count: { type: Number, default: 0 },
    poll_ms: { type: Number, default: 8000 },
    max_attachment_kb: { type: Number, default: 8192 },
});

const page = usePage();
const list = ref([...props.conversations]);
const conversation = ref(props.conversation);
const query = ref('');
const draft = ref('');
const file = ref(null);
const fileName = ref('');
const gif = ref(null);
const sending = ref(false);
const loading = ref(false);
const directoryOpen = ref(false);
const typingPeers = ref([]);
const scroller = ref(null);
const threadEnd = ref(null);
const composer = ref(null);
let pollTimer = null;
let typingTimer = null;
let typingHideTimer = null;
let markReadTimer = null;
let echoChannel = null;

const visibleList = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) {
        return list.value;
    }

    return list.value.filter((item) => {
        const hay = `${item.title || ''} ${item.subtitle || ''} ${item.peer?.name || ''}`.toLowerCase();
        return hay.includes(q);
    });
});

const filteredDirectory = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) {
        return props.directory;
    }

    return props.directory.filter((peer) => {
        const hay = `${peer.name || ''} ${peer.email || ''} ${peer.role_label || ''}`.toLowerCase();
        return hay.includes(q);
    });
});

const typingLabel = computed(() => {
    const names = typingPeers.value.map((item) => item.name).filter(Boolean);
    if (!names.length) {
        return '';
    }
    if (names.length === 1) {
        return `${names[0]} is typing…`;
    }
    if (names.length === 2) {
        return `${names[0]} and ${names[1]} are typing…`;
    }

    return 'Several people are typing…';
});

const initials = (name) =>
    String(name || 'I')
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();

const scrollThread = async () => {
    scrollChatToEnd(scroller, threadEnd);
};

const applyThread = (payload) => {
    conversation.value = payload;
    list.value = list.value.map((item) =>
        item.uid === payload.uid
            ? { ...item, ...payload, messages: undefined, participants: undefined, typing: undefined, unread: false }
            : item,
    );
    typingPeers.value = payload.typing || [];
    scrollThread();
};

const jsonHeaders = { headers: { Accept: 'application/json' } };

const queueMarkRead = () => {
    if (!conversation.value?.uid) {
        return;
    }

    window.clearTimeout(markReadTimer);
    markReadTimer = window.setTimeout(() => {
        markConversationRead();
    }, 450);
};

const markConversationRead = async () => {
    const uid = conversation.value?.uid;
    if (!uid) {
        return;
    }

    try {
        await axios.post(route('admin.asap.read', uid), {}, jsonHeaders);
        list.value = list.value.map((item) => (item.uid === uid ? { ...item, unread: false } : item));
        const unread = list.value.filter((item) => item.unread).length;
        bumpAsapShortcut(unread);
    } catch {
        // Ignore mark-read failures; next interaction retries.
    }
};

const appendEmoji = (emoji) => {
    draft.value = `${draft.value || ''}${emoji}`;
    composer.value?.focus();
};

const onGifPick = ({ url, name }) => {
    gif.value = { url, name };
    file.value = null;
    fileName.value = '';
    send();
};

const send = async () => {
    if (!conversation.value || (!draft.value.trim() && !file.value && !gif.value)) {
        return;
    }

    sending.value = true;
    const data = new FormData();
    if (draft.value.trim()) {
        data.append('body', draft.value.trim());
    }
    if (file.value) {
        data.append('attachment', file.value);
    }
    if (gif.value?.url) {
        data.append('gif_url', gif.value.url);
        data.append('gif_name', gif.value.name || 'GIF');
    }

    try {
        const { data: payload } = await axios.post(
            route('admin.asap.store', conversation.value.uid),
            data,
            jsonHeaders,
        );
        draft.value = '';
        file.value = null;
        fileName.value = '';
        gif.value = null;
        applyThread(payload);
        queueMarkRead();
    } finally {
        sending.value = false;
    }
};

const startDirect = (userId) => {
    directoryOpen.value = false;
    router.post(route('admin.asap.direct'), { user_id: userId });
};

const onFile = (event) => {
    file.value = event.target.files?.[0] || null;
    fileName.value = file.value?.name || '';
    if (file.value) {
        gif.value = null;
    }
};

const onDraft = () => {
    window.clearTimeout(typingTimer);
    typingTimer = window.setTimeout(() => {
        if (!conversation.value) {
            return;
        }
        axios.post(route('admin.asap.typing', conversation.value.uid), {}, jsonHeaders).catch(() => {});
    }, 280);
};

const sync = async () => {
    try {
        const { data } = await axios.get(route('admin.asap.sync'), {
            ...jsonHeaders,
            params: { conversation: conversation.value?.uid },
        });
        list.value = data.conversations || [];
        if (data.conversation) {
            const last = conversation.value?.messages?.at(-1)?.id;
            conversation.value = data.conversation;
            typingPeers.value = data.conversation.typing || [];
            if (data.conversation.messages?.at(-1)?.id !== last) {
                scrollThread();
            }
        }
        bumpAsapShortcut(data.unread_count);
    } catch {
        // Keep current inbox if a poll fails.
    }
};

const bumpAsapShortcut = (count) => {
    const inbox = page.props.ops_inbox;
    if (!inbox?.shortcuts) {
        return;
    }

    inbox.shortcuts = inbox.shortcuts.map((item) =>
        item.key === 'asap' ? { ...item, count: Number(count || 0) } : item,
    );
};

const onStaffChat = () => {
    sync();
};

const onStaffTyping = (event) => {
    const detail = event.detail || {};
    if (!conversation.value || detail.conversation_uid !== conversation.value.uid) {
        return;
    }
    if (Number(detail.user?.id) === Number(page.props.auth?.user?.id)) {
        return;
    }

    const next = [...typingPeers.value.filter((item) => item.id !== detail.user.id), detail.user];
    typingPeers.value = next;
    window.clearTimeout(typingHideTimer);
    typingHideTimer = window.setTimeout(() => {
        typingPeers.value = typingPeers.value.filter((item) => item.id !== detail.user.id);
    }, 4000);
};

const bindEcho = () => {
    const echo = echoClient(page.props.reverb);
    if (!echo || !conversation.value?.uid) {
        return;
    }

    echoChannel?.stopListening('.staff.chat');
    echoChannel?.stopListening('.staff.typing');
    echo?.leave(`staff.chat.${conversation.value.uid}`);

    echoChannel = echo.private(`staff.chat.${conversation.value.uid}`)
        .listen('.staff.chat', () => sync())
        .listen('.staff.typing', (payload) => {
            window.dispatchEvent(new CustomEvent('isabi:staff-typing', { detail: payload }));
        });
};

watch(() => props.conversations, (value) => {
    list.value = [...value];
});

watch(() => props.conversation, (value) => {
    conversation.value = value;
    typingPeers.value = value?.typing || [];
    scrollThread();
    bindEcho();
    if (value?.uid) {
        nextTick(() => queueMarkRead());
    }
});

onMounted(() => {
    scrollThread();
    bindEcho();
    window.addEventListener('isabi:staff-chat', onStaffChat);
    window.addEventListener('isabi:staff-typing', onStaffTyping);
    pollTimer = window.setInterval(sync, props.poll_ms);
    if (conversation.value?.uid) {
        queueMarkRead();
    }
});

onUnmounted(() => {
    window.clearInterval(pollTimer);
    window.clearTimeout(typingTimer);
    window.clearTimeout(typingHideTimer);
    window.clearTimeout(markReadTimer);
    window.removeEventListener('isabi:staff-chat', onStaffChat);
    window.removeEventListener('isabi:staff-typing', onStaffTyping);
    const echo = echoClient(page.props.reverb);
    if (echo && conversation.value?.uid) {
        echo.leave(`staff.chat.${conversation.value.uid}`);
    }
});
</script>
