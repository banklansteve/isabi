<template>
    <Head :title="notice.letter_subject" />

    <AdminChrome :title="notice.letter_subject" :eyebrow="notice.reference" />

    <article class="mx-auto max-w-2xl rounded-2xl bg-white p-6 shadow-premium ring-1 ring-ink/[0.05] sm:p-8">
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-ink/35">{{ notice.type_label }}</p>
        <p class="mt-2 text-xs font-medium text-ink/40">Issued {{ notice.issued_label }}</p>
        <div class="mt-6 whitespace-pre-wrap text-sm font-medium leading-relaxed text-ink/80">{{ notice.letter_body }}</div>
        <a :href="route('admin.notices.letter', notice.id)" class="mt-6 inline-flex text-xs font-semibold text-base-action hover:text-base-hover">
            Download PDF
        </a>
    </article>

    <div class="mx-auto mt-5 max-w-2xl space-y-4">
        <form
            v-if="!notice.acknowledged"
            class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05]"
            @submit.prevent="acknowledge"
        >
            <label class="flex items-start gap-3 text-sm font-medium leading-relaxed text-ink/70">
                <input v-model="ackForm.acknowledged" type="checkbox" class="mt-1 rounded border-ink/20" required />
                I acknowledge I have received and reviewed this notice.
            </label>
            <p v-if="ackForm.errors.acknowledged" class="mt-2 text-xs font-medium text-coral-deep">{{ ackForm.errors.acknowledged }}</p>
            <FormButton class="mt-4" type="submit" variant="primary" label="Record acknowledgment" :loading="ackForm.processing" loading-label="Saving…" />
        </form>
        <p v-else class="text-sm font-medium text-ink/50">Acknowledged {{ notice.acknowledged_label }}.</p>

        <form
            v-if="notice.acknowledged && !notice.has_response"
            class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05]"
            @submit.prevent="respond"
        >
            <FormTextarea id="resp" v-model="responseForm.response" label="Written response (optional, one only)" :error="responseForm.errors.response" />
            <FormButton class="mt-4" type="submit" variant="secondary" label="Submit response" :loading="responseForm.processing" loading-label="Saving…" />
        </form>
        <div v-else-if="notice.has_response" class="rounded-2xl bg-pale/70 p-5">
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-ink/35">Your response</p>
            <p class="mt-2 whitespace-pre-wrap text-sm font-medium leading-relaxed text-ink/70">{{ notice.response_body }}</p>
        </div>

        <form
            v-if="notice.can_appeal && !notice.appeal_pending"
            class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05]"
            @submit.prevent="appeal"
        >
            <FormTextarea id="grounds" v-model="appealForm.grounds" label="Grounds for appeal" :error="appealForm.errors.grounds" required />
            <FormButton class="mt-4" type="submit" variant="secondary" label="Submit appeal" :loading="appealForm.processing" loading-label="Saving…" />
        </form>
        <p v-else-if="notice.appeal_pending" class="text-sm font-medium text-ink/50">An appeal on this notice is waiting for review.</p>
    </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    notice: { type: Object, required: true },
});

const ackForm = useForm({ acknowledged: false });
const responseForm = useForm({ response: '' });
const appealForm = useForm({ grounds: '' });

const acknowledge = () => {
    ackForm.post(route('admin.notices.acknowledge', props.notice.id), { preserveScroll: true });
};

const respond = () => {
    responseForm.post(route('admin.notices.respond', props.notice.id), { preserveScroll: true });
};

const appeal = () => {
    appealForm.post(route('admin.notices.appeal', props.notice.id), { preserveScroll: true });
};
</script>
