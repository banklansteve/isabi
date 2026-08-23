<template>
    <section class="space-y-6">
        <header v-if="showHeader">
            <h2 class="text-lg font-bold tracking-tight text-ink">
                Review WhatsApp messages
            </h2>
            <p class="mt-1 text-sm font-medium text-ink/50">
                Set the wording once — every invite and reminder uses it.
            </p>
        </header>

        <Transition
            mode="out-in"
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
        >
        <!-- View -->
        <div v-if="!editing" key="view" class="space-y-5">
            <div class="flex justify-end">
                <button
                    type="button"
                    class="tap-target inline-flex items-center gap-2 rounded-2xl bg-base-action px-4 py-2.5 text-sm font-bold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.45)] transition-colors hover:bg-base-hover"
                    @click="startEditing"
                >
                    <i class="ti ti-pencil" aria-hidden="true" />
                    Edit
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.08em] text-ink/40">
                        First message to client
                    </p>
                    <p
                        class="mt-2 rounded-2xl bg-pale px-4 py-3.5 text-sm font-medium leading-relaxed text-ink/75 ring-1 ring-ink/[0.05]"
                    >
                        {{ invitePreview }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.08em] text-ink/40">
                        Follow-up reminder
                    </p>
                    <p
                        class="mt-2 rounded-2xl bg-pale px-4 py-3.5 text-sm font-medium leading-relaxed text-ink/75 ring-1 ring-ink/[0.05]"
                    >
                        {{ reminderPreview }}
                    </p>
                </div>

                <div class="grid gap-1 sm:grid-cols-[8.5rem_1fr] sm:gap-4">
                    <p class="text-xs font-bold uppercase tracking-[0.08em] text-ink/40">
                        When to remind
                    </p>
                    <p class="text-sm font-semibold text-ink">
                        {{ reminderDaysLabel }}
                    </p>
                </div>
            </div>

            <p class="rounded-2xl bg-tint/50 px-4 py-3 text-xs font-medium leading-relaxed text-ink/55">
                Isabi fills in the client’s name, the job, and your review link when you open WhatsApp.
                You still tap send yourself — nothing goes out on its own.
            </p>
        </div>

        <!-- Edit -->
        <form v-else key="edit" class="space-y-5" @submit.prevent="submit">
            <p class="rounded-2xl bg-tint/50 px-4 py-3 text-xs font-medium leading-relaxed text-ink/55">
                Write like you’re texting a client. We’ll add their name, the job you did, and your
                review link automatically when you send the WhatsApp.
            </p>

            <div>
                <div class="mb-2 flex items-end justify-between gap-3">
                    <label for="review_invite_template" class="text-sm font-bold text-ink">
                        First message
                    </label>
                    <button
                        type="button"
                        class="text-[11px] font-bold text-base-action hover:text-base-hover"
                        @click="resetInvite"
                    >
                        Use suggested text
                    </button>
                </div>
                <FormTextarea
                    id="review_invite_template"
                    v-model="form.review_invite_template"
                    :rows="4"
                    :error="form.errors.review_invite_template"
                    hint="Keep it warm and short — clients respond faster."
                />
                <p
                    class="mt-2 rounded-xl bg-pale px-3.5 py-2.5 text-[12px] font-medium leading-relaxed text-ink/55 ring-1 ring-ink/[0.05]"
                >
                    <span class="font-bold text-ink/40">How it will look · </span>
                    {{ invitePreview }}
                </p>
            </div>

            <div>
                <div class="mb-2 flex items-end justify-between gap-3">
                    <label for="review_reminder_template" class="text-sm font-bold text-ink">
                        Gentle reminder
                    </label>
                    <button
                        type="button"
                        class="text-[11px] font-bold text-base-action hover:text-base-hover"
                        @click="resetReminder"
                    >
                        Use suggested text
                    </button>
                </div>
                <FormTextarea
                    id="review_reminder_template"
                    v-model="form.review_reminder_template"
                    :rows="3"
                    :error="form.errors.review_reminder_template"
                    hint="One polite follow-up if they haven’t left a review yet."
                />
                <p
                    class="mt-2 rounded-xl bg-pale px-3.5 py-2.5 text-[12px] font-medium leading-relaxed text-ink/55 ring-1 ring-ink/[0.05]"
                >
                    <span class="font-bold text-ink/40">How it will look · </span>
                    {{ reminderPreview }}
                </p>
            </div>

            <FormSelect
                id="review_reminder_days"
                v-model="form.review_reminder_days"
                label="When should we remind you to follow up?"
                icon="ti ti-bell"
                :options="dayOptions"
                :error="form.errors.review_reminder_days"
                hint="We’ll show the job in your due list. You still open WhatsApp and send — same as the first message."
            />

            <div class="flex flex-wrap gap-2">
                <FormButton
                    type="submit"
                    variant="primary"
                    label="Save messages"
                    :loading="form.processing"
                    loading-label="Saving…"
                    icon-right="ti ti-check"
                />
                <FormButton type="button" variant="ghost" label="Cancel" @click="cancelEditing" />
            </div>
        </form>
        </Transition>
    </section>
</template>

<script setup>
import FormButton from '@/Components/Form/FormButton.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    profile: { type: Object, required: true },
    defaults: { type: Object, required: true },
    showHeader: { type: Boolean, default: true },
});

const editing = ref(false);

const blankForm = () => ({
    review_invite_template: props.profile.review_invite_template || props.defaults.invite,
    review_reminder_template: props.profile.review_reminder_template || props.defaults.reminder,
    review_reminder_days: String(
        props.profile.review_reminder_days ?? props.defaults.default_reminder_days ?? 3,
    ),
});

const form = useForm(blankForm());

const dayOptions = [
    { value: '0', label: 'Don’t remind me — I’ll follow up myself' },
    { value: '2', label: 'Remind me after 2 days' },
    { value: '3', label: 'Remind me after 3 days' },
    { value: '5', label: 'Remind me after 5 days' },
    { value: '7', label: 'Remind me after 1 week' },
    { value: '10', label: 'Remind me after 10 days' },
    { value: '14', label: 'Remind me after 2 weeks' },
];

const reminderDaysLabel = computed(() => {
    const match = dayOptions.find((o) => o.value === String(form.review_reminder_days));
    return match?.label || 'Remind me after 3 days';
});

const renderPreview = (template) => {
    const name = 'Ada';
    const greeting = `Hi ${name},`;
    const job = 'rewiring';
    const link = 'https://isabi.dev/r/…';

    return String(template || '')
        .replaceAll('{greeting}', greeting)
        .replaceAll('{client_name}', name)
        .replaceAll('{job}', job)
        .replaceAll('{link}', link)
        .replace(/\s{2,}/g, ' ')
        .trim();
};

const invitePreview = computed(() => renderPreview(form.review_invite_template));
const reminderPreview = computed(() => renderPreview(form.review_reminder_template));

const resetInvite = () => {
    form.review_invite_template = props.defaults.invite;
};

const resetReminder = () => {
    form.review_reminder_template = props.defaults.reminder;
};

const startEditing = () => {
    Object.assign(form, blankForm());
    form.clearErrors();
    editing.value = true;
};

const cancelEditing = () => {
    Object.assign(form, blankForm());
    form.clearErrors();
    editing.value = false;
};

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            review_reminder_days: Number(data.review_reminder_days),
            review_invite_template:
                data.review_invite_template?.trim() === props.defaults.invite.trim()
                    ? null
                    : data.review_invite_template,
            review_reminder_template:
                data.review_reminder_template?.trim() === props.defaults.reminder.trim()
                    ? null
                    : data.review_reminder_template,
        }))
        .patch(route('profile.review-messages'), {
            preserveScroll: true,
            onSuccess: () => {
                editing.value = false;
            },
        });
};
</script>
