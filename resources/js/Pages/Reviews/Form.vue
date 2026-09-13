<template>
    <div class="min-h-dvh bg-pale text-ink">
        <Head :title="`Review ${artisan.name}`" />

        <!-- Hero -->
        <header class="relative overflow-hidden bg-ink text-white">
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="absolute -left-24 -top-32 h-72 w-72 rounded-full bg-base/30 blur-3xl" />
                <div class="absolute -right-16 top-4 h-56 w-56 rounded-full bg-coral/20 blur-3xl" />
                <div
                    class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(255,255,255,0.12),transparent_58%)]"
                />
            </div>

            <div
                class="relative mx-auto max-w-3xl px-5 pb-16 sm:px-8 sm:pb-20"
                style="padding-top: max(0.85rem, env(safe-area-inset-top))"
            >
                <div class="flex items-center justify-between gap-4 py-2">
                    <Link
                        :href="route('home')"
                        class="font-display text-[1.3rem] font-extrabold tracking-tight text-white"
                    >
                        Kraftrack
                    </Link>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.16em] text-white/70 ring-1 ring-white/15 backdrop-blur"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-[#F5A524]" aria-hidden="true" />
                        Private invite
                    </span>
                </div>

                <div class="mt-8 flex items-center gap-4 sm:mt-10 sm:gap-5">
                    <div
                        class="flex h-[4.25rem] w-[4.25rem] shrink-0 items-center justify-center overflow-hidden rounded-[1.35rem] bg-gradient-to-br from-base to-deep text-xl font-extrabold text-white shadow-[0_18px_40px_-16px_rgba(0,0,0,0.9)] ring-1 ring-white/20 sm:h-20 sm:w-20"
                    >
                        <img
                            v-if="artisan.avatar_url"
                            :src="artisan.avatar_url"
                            :alt="artisan.name"
                            class="h-full w-full object-cover"
                        />
                        <span v-else class="font-display text-2xl">{{ artisan.initials }}</span>
                    </div>

                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-white/45">
                            You’re reviewing
                        </p>
                        <h1
                            class="mt-1.5 truncate font-display text-[1.7rem] font-extrabold leading-tight tracking-tight sm:text-[2.25rem]"
                        >
                            {{ artisan.name }}
                        </h1>
                        <p v-if="artisan.trade" class="mt-1 truncate text-sm font-medium text-white/55">
                            {{ artisan.trade }}
                        </p>
                    </div>
                </div>

                <!-- Job context -->
                <div
                    class="mt-7 rounded-[1.35rem] bg-white/[0.07] p-4 ring-1 ring-white/[0.12] backdrop-blur-sm sm:p-5"
                >
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-white/40">
                        The job
                    </p>
                    <p class="mt-2 text-[15px] font-semibold leading-relaxed text-white/90">
                        {{ job.description }}
                    </p>
                    <div class="mt-3.5 flex flex-wrap gap-2">
                        <span
                            v-if="job.category_label || job.job_category"
                            class="rounded-full bg-white/[0.12] px-2.5 py-1 text-[11px] font-bold text-white/80"
                        >
                            {{ job.category_label || job.job_category }}
                        </span>
                        <span
                            v-if="job.worked_on_label"
                            class="inline-flex items-center gap-1 rounded-full bg-white/[0.08] px-2.5 py-1 text-[11px] font-semibold text-white/60"
                        >
                            <i class="ti ti-calendar text-[12px]" aria-hidden="true" />
                            {{ job.worked_on_label }}
                        </span>
                        <span
                            v-if="job.service_label"
                            class="inline-flex items-center gap-1 rounded-full bg-white/[0.08] px-2.5 py-1 text-[11px] font-semibold text-white/60"
                        >
                            <i class="ti ti-map-pin text-[12px]" aria-hidden="true" />
                            {{ job.service_label }}
                        </span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Form sheet -->
        <main class="relative z-10 -mt-9 pb-10 sm:-mt-12 sm:pb-16">
            <form class="mx-auto max-w-3xl sm:px-8" @submit.prevent="submit">
                <AppInlineAlert
                    v-if="formHasErrors"
                    class="mb-4 sm:mb-5"
                    tone="error"
                    title="Couldn’t send this review"
                    message="Check the fields below and try again. Your words matter — we’ll keep them safe once they go through."
                />

                <div
                    class="overflow-hidden rounded-t-[1.85rem] bg-white shadow-premium-hover ring-1 ring-ink/[0.05] sm:rounded-[1.85rem]"
                >
                    <!-- 1 · Rating -->
                    <section class="px-5 py-7 sm:px-9 sm:py-9">
                        <div class="flex items-center justify-center gap-2.5">
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-tint text-[11px] font-extrabold text-deep"
                            >
                                1
                            </span>
                            <h2 class="font-display text-lg font-extrabold tracking-tight text-ink sm:text-xl">
                                How was the work?
                            </h2>
                        </div>

                        <div class="mt-5 sm:mt-6">
                            <FormStarRating
                                v-model="form.rating"
                                align="center"
                                hint="Tap a star — left half for a half rating"
                                :error="form.errors.rating"
                            />
                        </div>
                    </section>

                    <div class="mx-5 h-px bg-ink/[0.06] sm:mx-9" />

                    <!-- 2 · Recommend -->
                    <section class="px-5 py-7 sm:px-9 sm:py-9">
                        <div class="flex items-start gap-2.5">
                            <span
                                class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-tint text-[11px] font-extrabold text-deep"
                            >
                                2
                            </span>
                            <h2 class="font-display text-lg font-extrabold leading-snug tracking-tight text-ink sm:text-xl">
                                Would you recommend {{ artisan.first_name }}?
                            </h2>
                        </div>
                        <p class="mt-1.5 ps-[2.15rem] text-xs font-medium text-ink/45">
                            This shows on their public page.
                        </p>

                        <div
                            class="mt-4 grid grid-cols-2 gap-3"
                            role="radiogroup"
                            aria-label="Would you recommend this artisan?"
                        >
                            <button
                                v-for="option in recommendOptions"
                                :key="option.label"
                                type="button"
                                role="radio"
                                :aria-checked="form.would_recommend === option.value"
                                class="tap-target relative flex min-h-[3.75rem] items-center justify-center gap-2.5 rounded-2xl px-4 text-sm font-extrabold transition-all duration-200 ease-out active:scale-[0.98]"
                                :class="
                                    form.would_recommend === option.value
                                        ? option.activeClass
                                        : 'bg-pale text-ink/50 ring-1 ring-ink/[0.07] hover:bg-tint/60 hover:text-ink'
                                "
                                @click="form.would_recommend = option.value"
                            >
                                <i :class="option.icon" class="text-xl" aria-hidden="true" />
                                {{ option.label }}
                                <i
                                    v-if="form.would_recommend === option.value"
                                    class="ti ti-check absolute right-3 top-3 text-sm"
                                    aria-hidden="true"
                                />
                            </button>
                        </div>

                        <p
                            v-if="form.errors.would_recommend"
                            class="mt-2.5 text-xs font-semibold text-coral"
                        >
                            {{ form.errors.would_recommend }}
                        </p>
                    </section>

                    <div class="mx-5 h-px bg-ink/[0.06] sm:mx-9" />

                    <!-- 3 · Comment -->
                    <section class="px-5 py-7 sm:px-9 sm:py-9">
                        <div class="flex items-start gap-2.5">
                            <span
                                class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-tint text-[11px] font-extrabold text-deep"
                            >
                                3
                            </span>
                            <h2 class="font-display text-lg font-extrabold leading-snug tracking-tight text-ink sm:text-xl">
                                Tell them why
                            </h2>
                        </div>
                        <p class="mt-1.5 ps-[2.15rem] text-xs font-medium text-ink/45">
                            A sentence or two is plenty.
                        </p>

                        <div class="mt-4">
                            <FormTextarea
                                id="comment"
                                v-model="form.comment"
                                placeholder="What went well? Anything the next client should know?"
                                :rows="4"
                                :error="form.errors.comment"
                            />
                        </div>
                    </section>

                    <div class="mx-5 h-px bg-ink/[0.06] sm:mx-9" />

                    <!-- Optional extras -->
                    <section class="px-5 py-5 sm:px-9 sm:py-6">
                        <button
                            type="button"
                            class="tap-target flex w-full items-center gap-3 rounded-2xl text-left transition-colors duration-200"
                            :aria-expanded="extrasOpen"
                            @click="extrasOpen = !extrasOpen"
                        >
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F5A524]/12 text-[#C57F0B] ring-1 ring-[#F5A524]/20"
                            >
                                <i class="ti ti-sparkles text-lg" aria-hidden="true" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-bold text-ink">
                                    Make it count
                                </span>
                                <span class="block text-xs font-medium text-ink/45">
                                    Your name, referrer or a photo — optional
                                </span>
                            </span>
                            <i
                                class="ti ti-chevron-down shrink-0 text-lg text-ink/30 transition-transform duration-200"
                                :class="extrasOpen ? 'rotate-180' : ''"
                                aria-hidden="true"
                            />
                        </button>

                        <div v-show="extrasOpen" class="mt-5 space-y-4">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <FormTextInput
                                    id="client_display_name"
                                    v-model="form.client_display_name"
                                    label="Your name"
                                    icon="ti ti-user"
                                    placeholder="First name is fine"
                                    :error="form.errors.client_display_name"
                                />
                                <FormTextInput
                                    id="referred_by"
                                    v-model="form.referred_by"
                                    label="Who referred you?"
                                    icon="ti ti-users"
                                    placeholder="A friend’s name"
                                    :error="form.errors.referred_by"
                                />
                            </div>

                            <FormFileUpload
                                id="photo"
                                v-model="photoFiles"
                                label="Photo of the finished work"
                                accept="image/jpeg,image/png,image/webp,image/gif"
                                button-label="Add a photo"
                                help-text="Images up to 5MB"
                                :multiple="false"
                                :max-files="1"
                                :error="form.errors.photo"
                            />
                        </div>
                    </section>
                </div>

                <!-- Submit — pinned on mobile, inline on desktop -->
                <div
                    class="sticky bottom-0 z-30 border-t border-ink/[0.06] bg-white/85 px-5 pt-3 backdrop-blur-xl sm:static sm:mt-6 sm:border-0 sm:bg-transparent sm:px-0 sm:pt-0 sm:backdrop-blur-none"
                    style="padding-bottom: max(0.85rem, env(safe-area-inset-bottom))"
                >
                    <FormButton
                        type="submit"
                        variant="primary"
                        block
                        icon-right="ti ti-arrow-right"
                        :loading="form.processing"
                        loading-label="Sending…"
                        label="Submit review"
                    />
                </div>

                <p
                    class="mx-auto mt-5 flex max-w-md items-start justify-center gap-2 px-5 text-center text-xs font-medium leading-relaxed text-ink/40 sm:px-0"
                >
                    <i class="ti ti-lock mt-0.5 shrink-0 text-sm" aria-hidden="true" />
                    <span>
                        No account needed. This review is tied to this job only —
                        {{ artisan.first_name }} can’t write or edit it.
                    </span>
                </p>
            </form>
        </main>
    </div>
</template>

<script setup>
import AppInlineAlert from '@/Components/App/AppInlineAlert.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormFileUpload from '@/Components/Form/FormFileUpload.vue';
import FormStarRating from '@/Components/Form/FormStarRating.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    token: { type: String, required: true },
    artisan: { type: Object, required: true },
    job: { type: Object, required: true },
});

const photoFiles = ref([]);
const extrasOpen = ref(false);

const recommendOptions = [
    {
        value: true,
        label: 'Yes',
        icon: 'ti ti-thumb-up',
        activeClass:
            'bg-emerald-500/[0.08] text-emerald-800 ring-2 ring-emerald-500/45 shadow-[0_10px_24px_-14px_rgba(16,185,129,0.9)]',
    },
    {
        value: false,
        label: 'No',
        icon: 'ti ti-thumb-down',
        activeClass: 'bg-ink/[0.06] text-ink ring-2 ring-ink/25 shadow-sm',
    },
];

const form = useForm({
    rating: 0,
    would_recommend: null,
    comment: '',
    client_display_name: '',
    referred_by: '',
    photo: null,
});

const formHasErrors = computed(() => Object.keys(form.errors || {}).length > 0);

watch(photoFiles, (files) => {
    form.photo = files?.[0] || null;
});

watch(
    () => form.errors,
    (errors) => {
        if (errors.client_display_name || errors.referred_by || errors.photo) {
            extrasOpen.value = true;
        }
    },
    { deep: true },
);

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            // Keep half-stars as an explicit decimal string for FormData.
            rating: data.rating > 0 ? Number(data.rating).toFixed(1) : data.rating,
            // Ensure booleans serialize correctly over multipart FormData.
            would_recommend:
                data.would_recommend === true
                    ? 1
                    : data.would_recommend === false
                        ? 0
                        : null,
        }))
        .post(route('reviews.store', props.token), {
            forceFormData: true,
        });
};
</script>
