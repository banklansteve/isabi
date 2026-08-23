<template>
    <Head :title="ticket.subject" />

    <AdminChrome :title="ticket.subject" eyebrow="Support thread" />
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-bold text-ink">{{ ticket.user?.name }}</p>
                <p class="text-[13px] font-medium text-ink/45">{{ ticket.user?.email }}</p>
            </div>
            <div class="flex gap-2">
                <Link
                    :href="route('admin.support.index')"
                    class="rounded-xl bg-pale px-3 py-2 text-sm font-semibold text-ink"
                >
                    Back to inbox
                </Link>
                <Link
                    v-if="ticket.status !== 'resolved'"
                    :href="route('admin.support.resolve', ticket.id)"
                    method="post"
                    as="button"
                    class="rounded-xl bg-ink px-3 py-2 text-sm font-semibold text-white"
                >
                    Mark resolved
                </Link>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5">
            <ul class="space-y-3">
                <li
                    v-for="message in ticket.messages"
                    :key="message.id"
                    class="flex"
                    :class="message.is_staff ? 'justify-end' : 'justify-start'"
                >
                    <div
                        class="max-w-[85%] rounded-2xl px-4 py-3 text-sm font-medium leading-relaxed sm:max-w-[75%]"
                        :class="
                            message.is_staff
                                ? 'rounded-br-md bg-ink text-white'
                                : 'rounded-bl-md bg-pale text-ink'
                        "
                    >
                        <p class="whitespace-pre-wrap">{{ message.body }}</p>
                        <p
                            class="mt-1.5 text-[11px] font-semibold"
                            :class="message.is_staff ? 'text-white/50' : 'text-ink/35'"
                        >
                            {{ message.author }} · {{ message.when }}
                        </p>
                    </div>
                </li>
            </ul>

            <form v-if="ticket.status !== 'resolved'" class="mt-5 flex items-end gap-2" @submit.prevent="reply">
                <textarea
                    v-model="form.body"
                    rows="2"
                    placeholder="Write a reply…"
                    class="min-h-12 flex-1 resize-none rounded-xl border border-ink/10 px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                />
                <button
                    type="submit"
                    class="tap-target flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-ink text-white disabled:opacity-40"
                    :disabled="form.processing || !form.body.trim()"
                    aria-label="Send reply"
                >
                    <i class="ti ti-send text-lg" aria-hidden="true" />
                </button>
            </form>
            <p v-if="form.errors.body" class="mt-2 text-xs font-semibold text-red-500">{{ form.errors.body }}</p>
        </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    ticket: { type: Object, required: true },
});

const form = useForm({ body: '' });

const reply = () => {
    form.post(route('admin.support.reply', props.ticket.id), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>
