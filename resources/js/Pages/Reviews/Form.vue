<template>
    <div class="min-h-dvh bg-pale text-ink">
        <Head :title="`Review ${artisan.name}`" />

        <header
            class="sticky top-0 z-40 border-b border-ink/10 bg-white/95 shadow-nav backdrop-blur-md"
        >
            <div
                class="mx-auto flex max-w-lg items-center justify-between gap-4 px-5 py-3"
                style="padding-top: max(0.75rem, env(safe-area-inset-top))"
            >
                <Link
                    :href="route('home')"
                    class="font-display text-[1.35rem] font-extrabold tracking-tight text-ink"
                >
                    Isabi
                </Link>
                <span class="text-xs font-semibold text-ink/40">Leave a review</span>
            </div>
        </header>

        <main class="mx-auto max-w-lg px-5 py-8 sm:py-10">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-tint text-lg font-bold text-deep ring-1 ring-ink/[0.06]"
                >
                    <img
                        v-if="artisan.avatar_url"
                        :src="artisan.avatar_url"
                        :alt="artisan.name"
                        class="h-full w-full object-cover"
                    />
                    <span v-else>{{ artisan.initials }}</span>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-base">
                        Review for
                    </p>
                    <h1 class="mt-0.5 truncate font-display text-2xl font-extrabold tracking-tight text-ink">
                        {{ artisan.name }}
                    </h1>
                    <p v-if="artisan.trade" class="mt-0.5 text-sm font-medium text-ink/50">
                        {{ artisan.trade }}
                    </p>
                </div>
            </div>

            <section
                class="mt-6 rounded-xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.06] sm:p-5"
            >
                <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink/40">
                    The job
                </p>
                <p class="mt-2 text-sm font-semibold leading-relaxed text-ink">
                    {{ job.description }}
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span
                        v-if="job.category_label || job.job_category"
                        class="rounded-full bg-tint px-2.5 py-1 text-[11px] font-bold text-deep"
                    >
                        {{ job.category_label || job.job_category }}
                    </span>
                    <span
                        v-if="job.worked_on_label"
                        class="rounded-full bg-pale px-2.5 py-1 text-[11px] font-semibold text-ink/50"
                    >
                        {{ job.worked_on_label }}
                    </span>
                </div>
            </section>

            <form class="mt-5 space-y-4" @submit.prevent="submit">
                <section class="rounded-xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-6">
                    <FormStarRating
                        v-model="form.rating"
                        label="How was the work?"
                        required
                        hint="Tap a star — honest ratings help other clients."
                        :error="form.errors.rating"
                    />

                    <div class="mt-5">
                        <FormTextarea
                            id="comment"
                            v-model="form.comment"
                            label="Your comment"
                            icon="ti ti-message"
                            placeholder="What went well? Anything others should know?"
                            :error="form.errors.comment"
                        />
                    </div>

                    <div class="mt-4">
                        <FormTextInput
                            id="client_display_name"
                            v-model="form.client_display_name"
                            label="Your name (optional)"
                            icon="ti ti-user"
                            placeholder="First name is fine"
                            :error="form.errors.client_display_name"
                        />
                    </div>

                    <div class="mt-4">
                        <FormTextInput
                            id="referred_by"
                            v-model="form.referred_by"
                            :label="`Who told you about ${artisan.first_name}? (optional)`"
                            icon="ti ti-users"
                            placeholder="A friend’s name, or leave blank"
                            hint="Helps map trust — who vouched for this artisan."
                            :error="form.errors.referred_by"
                        />
                    </div>

                    <div class="mt-4">
                        <FormFileUpload
                            id="photo"
                            v-model="photoFiles"
                            label="Photo of the finished work (optional)"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                            button-label="Add a photo"
                            help-text="Optional · images up to 5MB"
                            :multiple="false"
                            :max-files="1"
                            :error="form.errors.photo"
                        />
                    </div>
                </section>

                <FormButton
                    type="submit"
                    variant="primary"
                    block
                    icon-right="ti ti-send"
                    :loading="form.processing"
                    loading-label="Sending…"
                    label="Submit review"
                />

                <p class="text-center text-xs font-medium leading-relaxed text-ink/40">
                    No account needed. Your review is tied to this job only —
                    {{ artisan.first_name }} can’t write or edit it.
                </p>
            </form>
        </main>
    </div>
</template>

<script setup>
import FormButton from '@/Components/Form/FormButton.vue';
import FormFileUpload from '@/Components/Form/FormFileUpload.vue';
import FormStarRating from '@/Components/Form/FormStarRating.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    token: { type: String, required: true },
    artisan: { type: Object, required: true },
    job: { type: Object, required: true },
});

const photoFiles = ref([]);

const form = useForm({
    rating: 0,
    comment: '',
    client_display_name: '',
    referred_by: '',
    photo: null,
});

watch(photoFiles, (files) => {
    form.photo = files?.[0] || null;
});

const submit = () => {
    form.post(route('reviews.store', props.token), {
        forceFormData: true,
    });
};
</script>
