<template>
    <Head :title="ticket ? ticket.subject : 'Customer support'" />

    <AdminChrome
        v-if="!ticket"
        title="Customer support"
        :eyebrow="is_super ? 'Every conversation' : 'Live inbox'"
    />

    <SupportWorkspaceNav :counts="counts" />

    <div
        class="flex flex-col lg:h-[calc(100dvh-10rem)] lg:flex-row lg:overflow-hidden lg:rounded-2xl lg:bg-white lg:shadow-premium lg:ring-1 lg:ring-ink/[0.05]"
    >
        <aside
            class="flex w-full flex-col border-ink/[0.06] lg:w-[22rem] lg:shrink-0 lg:border-r"
            :class="ticket ? 'hidden lg:flex' : 'flex'"
        >
            <div class="space-y-2 border-b border-ink/[0.06] p-3">
                <input
                    v-model="query"
                    type="search"
                    placeholder="Search conversations…"
                    class="w-full rounded-xl border border-ink/10 bg-[#F4F6FA] px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                    @change="visitFilters"
                />
                <div class="flex gap-2">
                    <select
                        :value="filters.assigned"
                        class="min-w-0 flex-1 rounded-xl border border-ink/10 bg-[#F4F6FA] px-2.5 py-2 text-[12px] font-semibold text-ink outline-none"
                        @change="setFilter('assigned', $event.target.value)"
                    >
                        <option v-if="is_super" value="all">Anyone</option>
                        <option v-else value="me">Mine</option>
                        <option v-if="is_super" value="me">Mine</option>
                        <option value="unassigned">Unassigned</option>
                        <template v-if="is_super">
                            <option v-for="agent in agents" :key="agent.id" :value="String(agent.id)">
                                {{ agent.name }}
                            </option>
                        </template>
                    </select>
                </div>
                <div class="flex gap-1 rounded-full bg-[#F4F6FA] p-1">
                    <button
                        type="button"
                        class="flex-1 rounded-full px-2 py-1.5 text-[11px] font-bold"
                        :class="filters.assigned === 'unassigned' ? 'bg-white text-ink shadow-sm' : 'text-ink/45'"
                        @click="setFilter('assigned', 'unassigned')"
                    >
                        Unassigned
                        <span v-if="counts.unassigned" class="ms-0.5 text-coral-deep">{{ counts.unassigned }}</span>
                    </button>
                    <button
                        type="button"
                        class="flex-1 rounded-full px-2 py-1.5 text-[11px] font-bold"
                        :class="filters.assigned === 'me' || (!is_super && filters.assigned === 'all') ? 'bg-white text-ink shadow-sm' : 'text-ink/45'"
                        @click="setFilter('assigned', 'me')"
                    >
                        Mine
                        <span v-if="counts.mine" class="ms-0.5">{{ counts.mine }}</span>
                    </button>
                    <button
                        v-if="is_super"
                        type="button"
                        class="flex-1 rounded-full px-2 py-1.5 text-[11px] font-bold"
                        :class="filters.assigned === 'all' ? 'bg-white text-ink shadow-sm' : 'text-ink/45'"
                        @click="setFilter('assigned', 'all')"
                    >
                        All
                    </button>
                </div>
                <div class="flex gap-1 rounded-full bg-[#F4F6FA] p-1">
                    <button
                        type="button"
                        class="flex-1 rounded-full px-2 py-1.5 text-[11px] font-bold"
                        :class="filters.sort === 'waiting' ? 'bg-white text-ink shadow-sm' : 'text-ink/45'"
                        @click="setFilter('sort', 'waiting')"
                    >
                        Oldest waiting
                    </button>
                    <button
                        type="button"
                        class="flex-1 rounded-full px-2 py-1.5 text-[11px] font-bold"
                        :class="filters.sort === 'recent' ? 'bg-white text-ink shadow-sm' : 'text-ink/45'"
                        @click="setFilter('sort', 'recent')"
                    >
                        Recent
                    </button>
                </div>
            </div>

            <div v-if="loading && !visibleList.length" class="space-y-2 p-3">
                <div v-for="n in 6" :key="n" class="h-16 animate-pulse rounded-xl bg-pale" />
            </div>
            <AdminEmpty
                v-else-if="!visibleList.length"
                title="No conversations yet"
                description="When an artisan starts a chat, it will land here."
                icon="ti ti-headset"
            />
            <ul v-else class="min-h-0 flex-1 overflow-y-auto">
                <li v-for="item in visibleList" :key="item.id">
                    <Link
                        :href="route('admin.support.show', item.uid)"
                        prefetch
                        class="flex gap-3 border-b border-ink/[0.05] px-3.5 py-3 transition-colors hover:bg-pale/80"
                        :class="ticket?.uid === item.uid ? 'bg-tint/70' : ''"
                    >
                        <span class="relative mt-0.5 h-10 w-10 shrink-0 overflow-hidden rounded-full bg-tint text-[11px] font-bold text-deep">
                            <img v-if="item.user?.avatar_url" :src="item.user.avatar_url" alt="" class="h-full w-full object-cover" />
                            <span v-else class="flex h-full w-full items-center justify-center">{{ initials(item.user?.name) }}</span>
                            <span
                                v-if="item.unread"
                                class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full bg-base-action ring-2 ring-white"
                            />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-2">
                                <span class="truncate text-[13px] font-bold text-ink">{{ item.user?.name }}</span>
                                <span
                                    class="shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-bold"
                                    :class="queueStatusClass(item.queue_status)"
                                >
                                    {{ queueStatusLabel(item.queue_status) }}
                                </span>
                            </span>
                            <span class="mt-0.5 block truncate text-[12px] font-medium text-ink/45">{{ item.preview }}</span>
                            <span
                                class="mt-1 block text-[11px] font-bold"
                                :class="item.waiting_hot ? 'text-coral-deep' : 'text-ink/35'"
                            >
                                {{ item.waiting_label || item.last_activity }}
                            </span>
                        </span>
                    </Link>
                </li>
            </ul>
        </aside>

        <section
            class="min-w-0 flex-1 flex-col bg-[#F4F6FA]"
            :class="ticket ? 'flex' : 'hidden lg:flex'"
        >
            <div v-if="!ticket" class="flex flex-1 flex-col items-center justify-center px-6 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-ink/30 shadow-premium">
                    <i class="ti ti-messages text-2xl" aria-hidden="true" />
                </span>
                <p class="mt-4 text-sm font-bold text-ink">Pick a conversation</p>
                <p class="mt-1 max-w-sm text-[13px] font-medium text-ink/45">
                    Oldest unanswered chats rise to the top so nobody waits in the dark.
                </p>
            </div>

            <template v-else>
                <header class="flex items-center gap-3 border-b border-ink/[0.06] bg-white px-3 py-3 sm:px-4">
                    <Link
                        :href="route('admin.support.index', filters)"
                        class="tap-target flex h-10 w-10 items-center justify-center rounded-xl text-ink/40 hover:bg-pale lg:hidden"
                        aria-label="Back to inbox"
                    >
                        <i class="ti ti-arrow-left text-lg" aria-hidden="true" />
                    </Link>
                    <span class="h-10 w-10 shrink-0 overflow-hidden rounded-full bg-tint text-[11px] font-bold text-deep">
                        <img v-if="ticket.user?.avatar_url" :src="ticket.user.avatar_url" alt="" class="h-full w-full object-cover" />
                        <span v-else class="flex h-full w-full items-center justify-center">{{ initials(ticket.user?.name) }}</span>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-ink">{{ ticket.user?.name }}</p>
                        <p class="truncate text-[12px] font-medium text-ink/40">{{ ticket.user?.email }}</p>
                    </div>
                    <span
                        class="hidden rounded-full px-2 py-1 text-[11px] font-bold sm:inline"
                        :class="queueStatusClass(ticket.queue_status)"
                    >
                        {{ queueStatusLabel(ticket.queue_status) }}
                    </span>
                    <button
                        v-if="ticket.status !== 'resolved' && canPickUp"
                        type="button"
                        class="tap-target rounded-xl bg-base-action px-3 py-2 text-[12px] font-semibold text-white hover:bg-base-hover"
                        @click="askConfirm('claim')"
                    >
                        {{ ticket.assigned?.id && is_super ? 'Take over' : 'Pick up' }}
                    </button>
                    <button
                        v-if="ticket.status !== 'resolved'"
                        type="button"
                        class="tap-target rounded-xl px-3 py-2 text-[12px] font-semibold"
                        :class="canPickUp ? 'bg-pale text-ink' : 'bg-base-action text-white hover:bg-base-hover'"
                        @click="askConfirm('resolve')"
                    >
                        Resolve
                    </button>
                    <button
                        v-else
                        type="button"
                        class="tap-target rounded-xl bg-pale px-3 py-2 text-[12px] font-semibold text-ink"
                        @click="askConfirm('reopen')"
                    >
                        Reopen
                    </button>
                </header>

                <div class="flex min-h-0 flex-1 flex-col lg:flex-row">
                    <div class="flex min-h-[min(100dvh-8rem,920px)] min-w-0 flex-col lg:min-h-0 lg:flex-1">
                        <div ref="scroller" class="min-h-0 flex-1 space-y-3 overflow-y-auto px-3 py-4 sm:px-5">
                            <div
                                v-for="(message, index) in ticket.messages"
                                :key="message.id"
                                class="flex"
                                :class="message.role === 'support' ? 'justify-end' : 'justify-start'"
                            >
                                <ChatBubbleReactions
                                    :reactions="message.reactions || []"
                                    :align="message.role === 'support' ? 'end' : 'start'"
                                    :endpoint="route('admin.support.react', [ticket.uid, message.id])"
                                    @updated="(reactions) => (message.reactions = reactions)"
                                >
                                    <div
                                        class="max-w-[85%] rounded-2xl px-4 py-3 text-sm font-medium leading-relaxed"
                                        :class="
                                            message.role === 'support'
                                                ? 'rounded-br-md bg-base-action text-white'
                                                : 'rounded-bl-md bg-white text-ink ring-1 ring-ink/[0.06]'
                                        "
                                    >
                                        <a
                                            v-if="message.attachment?.image"
                                            :href="message.attachment.url"
                                            target="_blank"
                                            class="mb-2 block overflow-hidden rounded-xl"
                                        >
                                            <img
                                                :src="message.attachment.url"
                                                :alt="message.attachment.name"
                                                class="max-h-48 w-full object-cover"
                                                @load="scrollThread"
                                            />
                                        </a>
                                        <p v-if="message.body" class="whitespace-pre-wrap">{{ message.body }}</p>
                                        <p
                                            class="mt-1.5 text-[10px] font-semibold"
                                            :class="message.role === 'support' ? 'text-white/55' : 'text-ink/35'"
                                        >
                                            {{ message.author }} · {{ message.when }}
                                            <span v-if="message.role === 'support' && isLatestStaff(index) && ticket.seen"> · Seen</span>
                                        </p>
                                    </div>
                                </ChatBubbleReactions>
                            </div>
                            <div v-if="ticket.typing" class="flex justify-start">
                                <div class="rounded-2xl bg-white px-4 py-2.5 text-ink/40 ring-1 ring-ink/[0.06]">
                                    <SupportTypingDots :label="`${ticket.user?.name || 'Customer'} is typing…`" />
                                </div>
                            </div>
                            <div ref="threadEnd" class="h-px w-full shrink-0" aria-hidden="true" />
                        </div>

                        <form
                            v-if="ticket.status !== 'resolved'"
                            class="shrink-0 border-t border-ink/[0.06] bg-white p-3 sm:p-4"
                            style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))"
                            @submit.prevent="reply"
                        >
                            <div v-if="cannedOpen" class="mb-2 max-h-52 overflow-y-auto rounded-xl ring-1 ring-ink/[0.08]">
                                <div v-for="group in cannedGroups" :key="group.key">
                                    <p
                                        v-if="group.items.length"
                                        class="bg-pale px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-ink/40"
                                    >
                                        {{ group.label }}
                                    </p>
                                    <button
                                        v-for="item in group.items"
                                        :key="item.id"
                                        type="button"
                                        class="flex w-full flex-col items-start px-3 py-2 text-left hover:bg-pale"
                                        @click="insertCanned(item)"
                                    >
                                        <span class="text-[12px] font-bold text-ink">{{ item.title }}</span>
                                        <span class="truncate text-[11px] text-ink/40">{{ item.body }}</span>
                                    </button>
                                </div>
                                <p v-if="!filteredCanned.length" class="px-3 py-2 text-[12px] text-ink/40">No saved replies match.</p>
                            </div>
                            <div class="mb-2 flex gap-1 overflow-x-auto">
                                <button
                                    v-for="moment in moments"
                                    :key="moment.key"
                                    type="button"
                                    class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold"
                                    :class="cannedMoment === moment.key ? 'bg-tint text-deep' : 'bg-pale text-ink/45'"
                                    @click="openMoment(moment.key)"
                                >
                                    {{ moment.label }}
                                </button>
                            </div>
                            <textarea
                                v-model="draft"
                                rows="3"
                                placeholder="Write a reply — type / for saved replies"
                                class="min-h-[4.5rem] w-full resize-none rounded-xl border border-ink/10 px-3.5 py-3 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                                @keydown.enter.exact.prevent="reply"
                                @input="onDraft"
                            />
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <label class="tap-target flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl bg-pale text-ink/45 hover:text-ink">
                                    <i class="ti ti-paperclip" aria-hidden="true" />
                                    <span class="sr-only">Attach</span>
                                    <input type="file" class="hidden" accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" @change="onFile" />
                                </label>
                                <ChatEmojiPicker @pick="appendEmoji" />
                                <button
                                    type="button"
                                    class="tap-target rounded-xl px-3 py-2 text-[12px] font-semibold text-ink/50 hover:bg-pale"
                                    @click="cannedOpen = !cannedOpen"
                                >
                                    Saved replies
                                </button>
                                <span v-if="fileName" class="truncate text-[11px] font-medium text-ink/40">{{ fileName }}</span>
                                <button
                                    type="submit"
                                    class="ms-auto tap-target rounded-xl bg-base-action px-4 py-2 text-[13px] font-semibold text-white hover:bg-base-hover disabled:opacity-40"
                                    :disabled="sending || (!draft.trim() && !file)"
                                >
                                    Send
                                </button>
                            </div>
                        </form>
                    </div>

                    <aside class="flex w-full shrink-0 flex-col border-t border-ink/[0.06] bg-white lg:max-h-none lg:w-72 lg:border-l lg:border-t-0">
                        <div class="space-y-4 p-4">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Assignment</p>
                                <template v-if="is_super">
                                    <select
                                        :value="ticket.assigned?.id || ''"
                                        class="mt-2 w-full rounded-xl border border-ink/10 bg-[#F4F6FA] px-3 py-2 text-[13px] font-semibold outline-none"
                                        @change="assign($event.target.value)"
                                    >
                                        <option value="">Unassigned</option>
                                        <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                                            {{ agent.name }}{{ agent.online ? ' · online' : '' }}
                                        </option>
                                    </select>
                                </template>
                                <p v-else class="mt-2 text-[13px] font-semibold text-ink">
                                    {{ ticket.assigned?.name || 'Unassigned' }}
                                </p>
                                <button
                                    v-if="ticket.status !== 'resolved' && canPickUp"
                                    type="button"
                                    class="mt-2 w-full rounded-xl bg-base-action px-3 py-2 text-[12px] font-semibold text-white hover:bg-base-hover"
                                    @click="askConfirm('claim')"
                                >
                                    Pick up this chat
                                </button>
                                <button
                                    v-else-if="ticket.status !== 'resolved' && isMine"
                                    type="button"
                                    class="mt-2 w-full rounded-xl bg-pale px-3 py-2 text-[12px] font-semibold text-ink hover:bg-tint"
                                    @click="askConfirm('release')"
                                >
                                    Release chat
                                </button>
                                <button
                                    v-if="ticket.status !== 'resolved' && can_refer && (isMine || is_super || canPickUp)"
                                    type="button"
                                    class="mt-2 w-full rounded-xl bg-pale px-3 py-2 text-[12px] font-semibold text-ink hover:bg-tint"
                                    @click="referOpen = true"
                                >
                                    Refer to colleague
                                </button>
                                <button
                                    v-if="ticket.status !== 'resolved' && can_escalate && !is_super && !ticket.escalation"
                                    type="button"
                                    class="mt-2 w-full rounded-xl border border-violet-200 bg-violet-50 px-3 py-2 text-[12px] font-semibold text-violet-800 hover:bg-violet-100"
                                    @click="escalateOpen = true"
                                >
                                    Escalate to Super Admin
                                </button>
                                <div
                                    v-if="ticket.escalation"
                                    class="mt-3 rounded-xl bg-violet-50/80 p-3 ring-1 ring-violet-100"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-violet-700">Escalated</p>
                                            <p
                                                class="mt-1 text-[12px] font-semibold"
                                                :class="escalationStatusClass(ticket.escalation.tone)"
                                            >
                                                {{ ticket.escalation.status }}
                                            </p>
                                            <p v-if="ticket.escalation.note" class="mt-1 text-[12px] font-medium text-ink/60">{{ ticket.escalation.note }}</p>
                                        </div>
                                        <button
                                            v-if="is_super && ticket.escalation.tone !== 'resolved'"
                                            type="button"
                                            class="shrink-0 rounded-lg bg-base-action px-2.5 py-1.5 text-[11px] font-semibold text-white hover:bg-base-hover disabled:opacity-60"
                                            :disabled="confirmBusy"
                                            @click="askConfirm('resolveEscalation')"
                                        >
                                            Resolve
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Tags</p>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    <button
                                        v-for="topic in topics"
                                        :key="topic.key"
                                        type="button"
                                        class="rounded-full px-2.5 py-1 text-[11px] font-bold"
                                        :class="(ticket.tags || []).includes(topic.key) ? 'bg-tint text-deep' : 'bg-pale text-ink/45'"
                                        @click="toggleTag(topic.key)"
                                    >
                                        {{ topic.label }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="flex min-h-0 flex-1 flex-col border-t border-amber-200/80 bg-amber-50/80">
                            <p class="px-4 pt-3 text-[11px] font-bold uppercase tracking-[0.14em] text-amber-800/70">
                                Internal notes
                            </p>
                            <p class="px-4 pt-1 text-[11px] font-medium text-amber-800/55">
                                Never shown to the artisan.
                            </p>
                            <ul class="min-h-0 flex-1 space-y-3 overflow-y-auto px-4 py-3">
                                <li v-for="note in ticket.notes" :key="note.id" class="rounded-xl bg-white/80 px-3 py-2 ring-1 ring-amber-200/70">
                                    <p class="text-[13px] font-medium leading-relaxed text-ink">{{ note.body }}</p>
                                    <p class="mt-1 text-[10px] font-semibold text-ink/35">{{ note.author }} · {{ note.when }}</p>
                                </li>
                                <li v-if="!ticket.notes?.length" class="text-[12px] font-medium text-amber-800/50">No notes yet.</li>
                            </ul>
                            <form class="border-t border-amber-200/70 p-3" @submit.prevent="addNote">
                                <textarea
                                    v-model="noteDraft"
                                    rows="2"
                                    placeholder="Add a note for the team…"
                                    class="w-full resize-none rounded-xl border border-amber-200 bg-white px-3 py-2 text-[13px] outline-none focus:ring-4 focus:ring-amber-100"
                                />
                                <button
                                    type="submit"
                                    class="mt-2 w-full rounded-xl bg-amber-800 px-3 py-2 text-[12px] font-semibold text-white disabled:opacity-40"
                                    :disabled="!noteDraft.trim()"
                                >
                                    Add note
                                </button>
                            </form>
                            <form class="border-t border-amber-200/70 p-3" @submit.prevent="saveCanned">
                                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-amber-800/70">Save a reply</p>
                                <input
                                    v-model="cannedTitle"
                                    type="text"
                                    placeholder="Title"
                                    class="mt-2 w-full rounded-xl border border-amber-200 bg-white px-3 py-2 text-[13px] outline-none"
                                />
                                <button
                                    type="submit"
                                    class="mt-2 text-[12px] font-semibold text-amber-900 disabled:opacity-40"
                                    :disabled="!draft.trim() || !cannedTitle.trim()"
                                >
                                    Save current draft
                                </button>
                            </form>
                        </div>
                    </aside>
                </div>
            </template>
        </section>
    </div>

    <ReferToStaffDialog
        :open="referOpen"
        title="Refer this chat to a colleague?"
        description="They become the assigned agent immediately."
        confirm-label="Refer chat"
        :staff="staff"
        default-queue="support"
        :show-queue="false"
        :processing="referBusy"
        @close="referOpen = false"
        @confirm="submitRefer"
    />

    <EscalateToSuperDialog
        :open="escalateOpen"
        title="Escalate this chat to Super Admin?"
        description="Include what you tried and why you need a decision upstairs."
        :processing="escalateBusy"
        @close="escalateOpen = false"
        @confirm="submitEscalate"
    />

    <AdminConfirmDialog
        :open="!!confirmAction"
        :title="confirmMeta.title"
        :description="confirmMeta.description"
        :confirm-label="confirmMeta.confirmLabel"
        :tone="confirmMeta.tone"
        :require-reason="confirmMeta.requireReason !== false"
        :processing="confirmBusy"
        @close="confirmAction = null"
        @confirm="submitConfirm"
    />
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminConfirmDialog from '@/Components/Admin/AdminConfirmDialog.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import ReferToStaffDialog from '@/Components/Admin/ReferToStaffDialog.vue';
import EscalateToSuperDialog from '@/Components/Admin/EscalateToSuperDialog.vue';
import SupportWorkspaceNav from '@/Components/Admin/SupportWorkspaceNav.vue';
import SupportTypingDots from '@/Components/App/SupportTypingDots.vue';
import ChatBubbleReactions from '@/Components/Chat/ChatBubbleReactions.vue';
import ChatEmojiPicker from '@/Components/Chat/ChatEmojiPicker.vue';
import { echoClient, echoConnected } from '@/echo';
import { toast } from '@/utils/adminRange';
import { escalationStatusClass } from '@/utils/opsStatus';
import { queueStatusClass, queueStatusLabel } from '@/utils/supportChat';
import { scrollChatToEnd } from '@/utils/chatScroll';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    tickets: { type: Array, default: () => [] },
    ticket: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
    agents: { type: Array, default: () => [] },
    staff: { type: Array, default: () => [] },
    can_refer: { type: Boolean, default: false },
    can_escalate: { type: Boolean, default: false },
    topics: { type: Array, default: () => [] },
    canned: { type: Array, default: () => [] },
    moments: { type: Array, default: () => [] },
    counts: { type: Object, default: () => ({ active: 0, resolved: 0, unassigned: 0, mine: 0, open: 0 }) },
    is_super: { type: Boolean, default: false },
    poll_ms: { type: Number, default: 8000 },
});

const page = usePage();
const list = ref([...props.tickets].filter((item) => {
    if (props.is_super) {
        return true;
    }
    const id = item?.assigned?.id ?? null;
    const me = Number(page.props.auth?.user?.id || 0);

    return id == null || Number(id) === me;
}));
const ticket = ref(
    props.ticket && (props.is_super
        || !props.ticket.assigned?.id
        || Number(props.ticket.assigned.id) === Number(page.props.auth?.user?.id || 0))
        ? props.ticket
        : null,
);
const query = ref(props.filters?.q || '');
const draft = ref('');
const noteDraft = ref('');
const cannedTitle = ref('');
const cannedOpen = ref(false);
const cannedMoment = ref('');
const file = ref(null);
const fileName = ref('');
const referOpen = ref(false);
const referBusy = ref(false);
const escalateOpen = ref(false);
const escalateBusy = ref(false);
const confirmAction = ref(null);
const confirmBusy = ref(false);
const sending = ref(false);
const loading = ref(false);
const scroller = ref(null);
const threadEnd = ref(null);
let pollTimer = null;
let typingTimer = null;
let typingHideTimer = null;
let echoReady = false;

const meId = computed(() => Number(page.props.auth?.user?.id || 0));
const agentName = computed(() => page.props.auth?.user?.first_name || String(page.props.auth?.user?.name || 'Isabi').split(' ')[0]);

const assignedIdOf = (item) => {
    const id = item?.assigned?.id ?? item?.assigned_to_user_id ?? null;

    return id == null || id === '' ? null : Number(id);
};

const isVisibleChat = (item) => {
    if (props.is_super) {
        return true;
    }

    const assignedId = assignedIdOf(item);
    const filter = props.filters?.assigned || 'me';

    if (filter === 'unassigned') {
        return assignedId === null;
    }

    // Ops never see another agent's chats — only their own.
    return assignedId === meId.value;
};

const visibleList = computed(() => list.value.filter(isVisibleChat));

const isMine = computed(() => {
    if (!ticket.value) {
        return false;
    }

    return assignedIdOf(ticket.value) === meId.value;
});

const canPickUp = computed(() => {
    if (!ticket.value || ticket.value.status === 'resolved') {
        return false;
    }

    const assignedId = assignedIdOf(ticket.value);

    if (assignedId === null) {
        return true;
    }

    // Only Super Admin can take over another agent's chat.
    return props.is_super && assignedId !== meId.value;
});

const confirmMeta = computed(() => {
    const map = {
        resolve: {
            title: 'Resolve this chat?',
            description: 'The artisan will see it as closed. You can reopen it later if needed.',
            confirmLabel: 'Resolve chat',
            tone: 'default',
        },
        reopen: {
            title: 'Reopen this chat?',
            description: 'It returns to the active queue for follow-up.',
            confirmLabel: 'Reopen chat',
            tone: 'default',
        },
        claim: {
            title: ticket.value?.assigned?.id && props.is_super ? 'Take over this chat?' : 'Pick up this chat?',
            description: 'You become the assigned agent for this conversation.',
            confirmLabel: ticket.value?.assigned?.id && props.is_super ? 'Take over' : 'Pick up',
            tone: 'default',
        },
        release: {
            title: 'Release this chat?',
            description: 'It goes back to the unassigned queue for another agent.',
            confirmLabel: 'Release chat',
            tone: 'default',
        },
        resolveEscalation: {
            title: 'Mark this escalation resolved?',
            description: 'Closes the Super Admin queue item for this chat.',
            confirmLabel: 'Mark resolved',
            tone: 'default',
        },
    };

    return map[confirmAction.value] || { title: 'Confirm', description: '', confirmLabel: 'Confirm', tone: 'default' };
});

const filteredCanned = computed(() => {
    const q = draft.value.startsWith('/') ? draft.value.slice(1).toLowerCase() : '';
    let items = props.canned;
    if (cannedMoment.value) {
        items = items.filter((item) => item.moment === cannedMoment.value);
    }
    if (!q) return items;
    return items.filter((item) => item.title.toLowerCase().includes(q) || item.body.toLowerCase().includes(q));
});

const cannedGroups = computed(() => {
    const labels = Object.fromEntries((props.moments || []).map((item) => [item.key, item.label]));
    const order = ['open', 'close', 'review', 'general'];
    return order
        .map((key) => ({
            key,
            label: labels[key] || key,
            items: filteredCanned.value.filter((item) => (item.moment || 'general') === key),
        }))
        .filter((group) => group.items.length);
});

const initials = (name) =>
    String(name || 'I')
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();

const isLatestStaff = (index) => {
    const messages = ticket.value?.messages || [];
    const last = [...messages].reverse().find((item) => item.role === 'support');
    return last && messages[index]?.id === last.id;
};

const scrollThread = async () => {
    scrollChatToEnd(scroller, threadEnd);
};

const setFilter = (key, value) => {
    visitFilters({ [key]: value });
};

const visitFilters = (overrides = {}) => {
    router.get(route('admin.support.index'), {
        ...props.filters,
        q: query.value,
        ...overrides,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const applyThread = (payload) => {
    ticket.value = payload;
    list.value = list.value.map((item) => (item.id === payload.id ? { ...item, ...payload, messages: undefined, notes: undefined } : item));
    scrollThread();
};

const jsonHeaders = { headers: { Accept: 'application/json' } };

const appendEmoji = (emoji) => {
    draft.value = `${draft.value || ''}${emoji}`;
};

const reply = async () => {
    if (!ticket.value || (!draft.value.trim() && !file.value)) return;
    sending.value = true;
    const data = new FormData();
    if (draft.value.trim()) data.append('body', draft.value.trim());
    if (file.value) data.append('attachment', file.value);
    try {
        const { data: payload } = await axios.post(route('admin.support.reply', ticket.value.uid), data, jsonHeaders);
        draft.value = '';
        file.value = null;
        fileName.value = '';
        cannedOpen.value = false;
        applyThread(payload);
    } finally {
        sending.value = false;
    }
};

const addNote = async () => {
    if (!noteDraft.value.trim() || !ticket.value) return;
    const { data } = await axios.post(route('admin.support.notes.store', ticket.value.uid), { body: noteDraft.value }, jsonHeaders);
    noteDraft.value = '';
    applyThread(data);
};

const askConfirm = (action) => {
    confirmAction.value = action;
};

const submitConfirm = async ({ reason }) => {
    if (!ticket.value || !confirmAction.value) {
        return;
    }

    confirmBusy.value = true;
    try {
        if (confirmAction.value === 'claim') {
            const { data } = await axios.post(route('admin.support.claim', ticket.value.uid), { reason }, jsonHeaders);
            applyThread(data);
        } else if (confirmAction.value === 'release') {
            const { data } = await axios.post(route('admin.support.assign', ticket.value.uid), {
                assigned_to_user_id: null,
                reason,
            }, jsonHeaders);
            applyThread(data);
        } else if (confirmAction.value === 'resolve') {
            await axios.post(route('admin.support.resolve', ticket.value.uid), { reason }, jsonHeaders);
            ticket.value = null;
            confirmAction.value = null;
            router.get(route('admin.support.index', { ...props.filters, status: 'resolved', q: query.value }), {}, {
                preserveState: false,
                replace: true,
            });
            return;
        } else if (confirmAction.value === 'reopen') {
            const { data } = await axios.post(route('admin.support.reopen', ticket.value.uid), { reason }, jsonHeaders);
            applyThread(data);
        } else if (confirmAction.value === 'resolveEscalation') {
            const escalationId = ticket.value?.escalation?.id;
            if (!escalationId) {
                return;
            }
            const { data } = await axios.post(route('admin.escalations.complete', escalationId), { note: reason }, jsonHeaders);
            if (data.block) {
                ticket.value = { ...ticket.value, escalation: data.block };
            } else if (ticket.value?.escalation) {
                ticket.value = {
                    ...ticket.value,
                    escalation: { ...ticket.value.escalation, status: 'Resolved', tone: 'resolved' },
                };
            }
            toast(data.toast || { type: 'success', title: 'Resolved', message: 'Escalation closed.' });
        }
        confirmAction.value = null;
    } catch (error) {
        toast({
            type: 'error',
            title: 'Couldn’t save',
            message: error?.response?.data?.message || 'Try that again in a moment.',
        });
    } finally {
        confirmBusy.value = false;
    }
};

const assign = async (id) => {
    const { data } = await axios.post(route('admin.support.assign', ticket.value.uid), {
        assigned_to_user_id: id || null,
    }, jsonHeaders);
    applyThread(data);
};

const submitRefer = async ({ assignee_id, note }) => {
    if (!ticket.value) return;
    referBusy.value = true;
    try {
        const { data } = await axios.post(route('admin.referrals.store'), {
            subject_type: 'support',
            subject_uid: ticket.value.uid,
            assignee_id,
            note,
            queue: 'support',
        }, jsonHeaders);
        referOpen.value = false;
        toast(data.toast || { type: 'success', title: 'Referred', message: 'Chat handed to a colleague.' });
        router.reload({ only: ['tickets', 'ticket', 'counts'] });
    } catch (error) {
        const messageText = error?.response?.data?.errors?.assignee_id?.[0]
            || error?.response?.data?.errors?.note?.[0]
            || 'Try that again in a moment.';
        toast({ type: 'error', title: 'Couldn’t refer', message: messageText });
    } finally {
        referBusy.value = false;
    }
};

const submitEscalate = async ({ note }) => {
    if (!ticket.value) return;
    escalateBusy.value = true;
    try {
        const { data } = await axios.post(route('admin.escalations.store'), {
            subject_type: 'support',
            subject_uid: ticket.value.uid,
            note,
        }, jsonHeaders);
        escalateOpen.value = false;
        if (data.block) {
            ticket.value = { ...ticket.value, escalation: data.block };
        }
        toast(data.toast || { type: 'success', title: 'Escalated', message: 'Super Admin has been notified.' });
    } catch (error) {
        toast({
            type: 'error',
            title: 'Couldn’t escalate',
            message: error?.response?.data?.errors?.note?.[0] || 'Try that again in a moment.',
        });
    } finally {
        escalateBusy.value = false;
    }
};

const acknowledgeEscalationFromUrl = async () => {
    if (!page.props.auth?.user?.is_super_admin) {
        return;
    }
    try {
        const escalationId = new URL(page.url, window.location.origin).searchParams.get('escalation');
        if (!escalationId) {
            return;
        }
        await axios.post(route('admin.escalations.acknowledge', escalationId), {}, jsonHeaders);
    } catch {
        // Non-blocking.
    }
};

const toggleTag = async (key) => {
    const tags = new Set(ticket.value.tags || []);
    if (tags.has(key)) tags.delete(key);
    else tags.add(key);
    const { data } = await axios.post(route('admin.support.tags', ticket.value.uid), { tags: [...tags] }, jsonHeaders);
    applyThread(data);
};

const insertCanned = (item) => {
    const name = ticket.value?.user?.name || 'there';
    draft.value = String(item.body || '')
        .replaceAll('{name}', name)
        .replaceAll('{agent}', agentName.value);
    cannedOpen.value = false;
    cannedMoment.value = '';
};

const openMoment = (key) => {
    cannedMoment.value = cannedMoment.value === key ? '' : key;
    cannedOpen.value = true;
};

const saveCanned = () => {
    router.post(route('admin.support.canned.store'), {
        title: cannedTitle.value,
        body: draft.value,
        moment: cannedMoment.value || 'general',
        scope: 'personal',
    }, {
        preserveScroll: true,
        onSuccess: () => {
            cannedTitle.value = '';
        },
    });
};

const onFile = (event) => {
    file.value = event.target.files?.[0] || null;
    fileName.value = file.value?.name || '';
};

const onDraft = () => {
    cannedOpen.value = draft.value.startsWith('/');
    window.clearTimeout(typingTimer);
    typingTimer = window.setTimeout(() => {
        if (!ticket.value) return;
        axios.post(route('admin.support.typing', ticket.value.uid), {}, jsonHeaders).catch(() => {});
    }, 280);
};

const sync = async () => {
    try {
        const { data } = await axios.get(route('admin.support.sync'), {
            ...jsonHeaders,
            params: { ...props.filters, ticket: ticket.value?.uid, q: query.value },
        });
        list.value = (data.tickets || []).filter(isVisibleChat);
        if (data.ticket) {
            const last = ticket.value?.messages?.at(-1)?.id;
            ticket.value = data.ticket;
            if (data.ticket.messages?.at(-1)?.id !== last) scrollThread();
        }
    } catch {
        // Keep the current inbox if a poll fails.
    }
};

watch(() => props.tickets, (value) => {
    list.value = [...value].filter(isVisibleChat);
});
watch(() => props.ticket, (value) => {
    ticket.value = value && isVisibleChat(value) ? value : null;
    scrollThread();
});
watch(() => ticket.value?.messages?.length, () => {
    scrollThread();
});

const onInboxEvent = (event) => {
    const item = event.detail?.item;
    const thread = event.detail?.thread;
    if (!item) {
        return;
    }
    if (!isVisibleChat(item)) {
        list.value = list.value.filter((row) => row.id !== item.id);
        if (ticket.value?.id === item.id) {
            ticket.value = null;
        }
        return;
    }
    const exists = list.value.some((row) => row.id === item.id);
    list.value = exists
        ? list.value.map((row) => (row.id === item.id ? { ...row, ...item } : row))
        : [item, ...list.value];
    if (thread && ticket.value?.id === thread.id) {
        const last = ticket.value?.messages?.at(-1)?.id;
        ticket.value = thread;
        if (thread.messages?.at(-1)?.id !== last) {
            scrollThread();
        }
    }
};

const onTypingEvent = (event) => {
    const payload = event.detail;
    if (payload?.side !== 'customer' || !ticket.value || payload.ticket_id !== ticket.value.id) {
        return;
    }
    ticket.value = { ...ticket.value, typing: true };
    window.clearTimeout(typingHideTimer);
    typingHideTimer = window.setTimeout(() => {
        if (ticket.value) {
            ticket.value = { ...ticket.value, typing: false };
        }
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

onMounted(() => {
    scrollThread();
    bindEcho();
    acknowledgeEscalationFromUrl();
    window.addEventListener('isabi:support-inbox', onInboxEvent);
    window.addEventListener('isabi:support-typing', onTypingEvent);
    pollTimer = window.setInterval(() => {
        if (!echoReady && !echoConnected()) {
            sync();
        }
    }, Number(props.poll_ms || 8000));
});
onUnmounted(() => {
    window.clearInterval(pollTimer);
    window.clearTimeout(typingTimer);
    window.clearTimeout(typingHideTimer);
    window.removeEventListener('isabi:support-inbox', onInboxEvent);
    window.removeEventListener('isabi:support-typing', onTypingEvent);
});
</script>
