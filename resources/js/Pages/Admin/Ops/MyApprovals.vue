<template>
    <Head title="My approvals" />

    <AdminChrome
        title="My approvals"
        :eyebrow="pendingCount ? `${pendingCount} waiting on Super Admin` : 'Track requests you submitted'"
    />

    <div class="mx-auto max-w-4xl space-y-6">
        <OpsAssignedTabs />
        <section class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5">
            <h2 class="text-sm font-bold text-ink">Pending</h2>
            <p class="mt-1 text-[13px] font-medium text-ink/45">
                These actions need Super Admin approval before they take effect.
            </p>

            <AdminEmpty
                v-if="!pending.length"
                class="mt-4"
                title="Nothing waiting"
                description="When you suspend, delete, or remove content that needs approval, it shows up here."
                icon="ti ti-clock-hour-4"
            />

            <ul v-else class="mt-4 divide-y divide-ink/[0.06]">
                <li v-for="item in pending" :key="item.uid">
                    <button
                        type="button"
                        class="flex w-full flex-col gap-3 py-4 text-left transition-colors hover:bg-pale/40 sm:flex-row sm:items-start sm:px-1"
                        @click="openItem(item)"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-[13px] font-bold text-ink">{{ item.action_label }}</p>
                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-800">
                                    {{ item.status_label }}
                                </span>
                            </div>
                            <p v-if="item.subject_label" class="mt-1 text-[13px] font-semibold text-ink/60">
                                {{ item.subject_label }}
                            </p>
                            <p class="mt-1 text-[12px] font-medium text-ink/40">Submitted {{ item.when }}</p>
                            <p v-if="item.reason" class="mt-2 text-[13px] font-medium leading-relaxed text-ink/70">
                                {{ item.reason }}
                            </p>
                        </div>
                        <i class="ti ti-chevron-right hidden shrink-0 text-ink/25 sm:mt-1 sm:block" aria-hidden="true" />
                    </button>
                </li>
            </ul>
        </section>

        <section v-if="recent.length" class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5">
            <h2 class="text-sm font-bold text-ink">Recent decisions</h2>
            <ul class="mt-3 divide-y divide-ink/[0.06]">
                <li v-for="item in recent" :key="item.uid">
                    <button
                        type="button"
                        class="flex w-full flex-col gap-2 py-4 text-left transition-colors hover:bg-pale/40 sm:px-1"
                        @click="openItem(item)"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-[13px] font-bold text-ink">{{ item.action_label }}</p>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide" :class="statusClass(item.status)">
                                {{ item.status_label }}
                            </span>
                        </div>
                        <p v-if="item.subject_label" class="text-[13px] font-semibold text-ink/55">{{ item.subject_label }}</p>
                        <p class="text-[12px] font-medium text-ink/40">
                            <span v-if="item.reviewer?.name">{{ item.reviewer.name }} · </span>
                            {{ item.reviewed_when || item.when }}
                        </p>
                        <p v-if="item.review_note" class="text-[13px] font-medium text-ink/65">{{ item.review_note }}</p>
                    </button>
                </li>
            </ul>
        </section>
    </div>

    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="opacity-0"
        >
            <div v-if="drawer" class="fixed inset-0 z-[80]">
                <button type="button" class="absolute inset-0 bg-ink/40 backdrop-blur-[2px]" aria-label="Close" @click="drawer = null" />
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="translate-x-full"
                    leave-active-class="transition duration-150 ease-in"
                    leave-to-class="translate-x-full"
                >
                    <aside
                        v-if="drawer"
                        class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-2xl"
                        style="padding-bottom: env(safe-area-inset-bottom)"
                    >
                        <div class="flex items-center justify-between border-b border-ink/[0.06] px-4 py-4 sm:px-5">
                            <div class="min-w-0">
                                <p class="truncate text-[15px] font-bold text-ink">{{ drawer.action_label }}</p>
                                <p class="mt-0.5 text-[12px] font-medium text-ink/40">Submitted {{ drawer.submitted_at }}</p>
                            </div>
                            <button
                                type="button"
                                class="flex h-10 w-10 items-center justify-center rounded-xl text-ink/45 hover:bg-pale hover:text-ink"
                                aria-label="Close"
                                @click="drawer = null"
                            >
                                <i class="ti ti-x text-xl" aria-hidden="true" />
                            </button>
                        </div>

                        <div class="flex-1 overflow-y-auto px-4 py-5 sm:px-5">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide" :class="statusClass(drawer.status)">
                                {{ drawer.status_label }}
                            </span>

                            <dl class="mt-5 space-y-4">
                                <div v-if="drawer.subject_label">
                                    <dt class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Subject</dt>
                                    <dd class="mt-1">
                                        <Link
                                            v-if="drawer.subject_href"
                                            :href="drawer.subject_href"
                                            class="text-[13px] font-semibold text-base-action hover:underline"
                                        >
                                            {{ drawer.subject_label }}
                                        </Link>
                                        <p v-else class="text-[13px] font-semibold text-ink">{{ drawer.subject_label }}</p>
                                    </dd>
                                </div>

                                <div v-if="drawer.reason">
                                    <dt class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Your reason</dt>
                                    <dd class="mt-1 text-[13px] font-medium leading-relaxed text-ink/75">{{ drawer.reason }}</dd>
                                </div>

                                <div v-if="drawer.reviewer?.name">
                                    <dt class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Reviewed by</dt>
                                    <dd class="mt-1 text-[13px] font-semibold text-ink">{{ drawer.reviewer.name }}</dd>
                                </div>

                                <div v-if="drawer.reviewed_at">
                                    <dt class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Decision time</dt>
                                    <dd class="mt-1 text-[13px] font-medium text-ink/60">{{ drawer.reviewed_at }}</dd>
                                </div>

                                <div v-if="drawer.review_note">
                                    <dt class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Super Admin note</dt>
                                    <dd class="mt-1 text-[13px] font-medium leading-relaxed text-ink/75">{{ drawer.review_note }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div v-if="drawer.subject_href" class="border-t border-ink/[0.06] p-4 sm:p-5">
                            <Link
                                :href="drawer.subject_href"
                                class="inline-flex w-full items-center justify-center rounded-xl bg-base-action px-4 py-3 text-[13px] font-semibold text-white shadow-[0_8px_20px_-10px_rgba(26,79,181,0.55)] transition-colors hover:bg-base-hover"
                            >
                                Open subject
                            </Link>
                        </div>
                    </aside>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import OpsAssignedTabs from '@/Components/Admin/OpsAssignedTabs.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    pending: { type: Array, default: () => [] },
    recent: { type: Array, default: () => [] },
    pending_count: { type: Number, default: 0 },
    opened_uid: { type: String, default: '' },
    opened: { type: Object, default: null },
});

const pendingCount = props.pending_count || props.pending.length;
const drawer = ref(props.opened || null);

watch(
    () => props.opened,
    (value) => {
        drawer.value = value || null;
    },
);

const openItem = (item) => {
    drawer.value = item;
};

const statusClass = (status) => {
    if (status === 'approved') return 'bg-emerald-50 text-emerald-700';
    if (status === 'rejected') return 'bg-red-50 text-red-600';
    if (status === 'pending') return 'bg-amber-50 text-amber-800';
    return 'bg-pale text-ink/50';
};
</script>
