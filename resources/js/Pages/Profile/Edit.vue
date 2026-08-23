<template>
    <Head title="Account" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-5xl">
            <section
                class="profile-hero relative mb-6 overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-7 shadow-premium-ink sm:mb-8 sm:px-7 sm:py-8"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(45%_55%_at_100%_100%,rgba(255,106,61,0.14),transparent_50%)]"
                    aria-hidden="true"
                />
                <div
                    class="pointer-events-none absolute inset-0 opacity-[0.18]"
                    style="
                        background-image: radial-gradient(rgba(255, 255, 255, 0.1) 0.7px, transparent 0.7px);
                        background-size: 18px 18px;
                    "
                    aria-hidden="true"
                />

                <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <div class="flex min-w-0 items-center gap-4">
                        <UserAvatar
                            :src="profile.avatar_url"
                            :initials="initials"
                            :alt="displayName"
                            size="hero"
                            class="bg-white shadow-sm"
                        />
                        <div class="min-w-0">
                            <p
                                class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/55"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-coral" aria-hidden="true" />
                                Account
                            </p>
                            <h1
                                class="mt-2 truncate font-editorial text-[2rem] font-semibold leading-[1.1] tracking-tight text-white sm:text-[2.35rem]"
                            >
                                {{ displayName }}
                            </h1>
                            <p class="mt-2 max-w-md text-sm font-medium leading-relaxed text-white/65">
                                Trade, coverage, bio, and review messages — what clients see on your page.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                        <Link
                            v-if="profile.public_url"
                            :href="route('page.index')"
                            class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-white/10 px-4 py-3 text-sm font-semibold text-white ring-1 ring-white/15 transition-colors hover:bg-white/16"
                        >
                            <i class="ti ti-user-circle" aria-hidden="true" />
                            My page
                        </Link>
                        <Link
                            :href="route('dashboard')"
                            class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-coral px-5 py-3 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(255,106,61,0.55)] transition-colors hover:bg-coral-deep"
                        >
                            Done
                            <i class="ti ti-arrow-right" aria-hidden="true" />
                        </Link>
                    </div>
                </div>
            </section>

            <nav
                class="profile-nav mb-5 flex gap-1.5 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] sm:mb-6 sm:flex-wrap sm:justify-center [&::-webkit-scrollbar]:hidden"
                aria-label="Settings sections"
            >
                <button
                    v-for="section in sections"
                    :key="section.id"
                    type="button"
                    class="tap-target inline-flex shrink-0 items-center gap-2 rounded-full px-3.5 py-2 text-xs font-bold transition-all duration-200"
                    :class="
                        activeSection === section.id
                            ? section.id === 'section-danger'
                                ? 'bg-coral text-white shadow-sm'
                                : 'bg-base-action text-white shadow-sm'
                            : 'bg-white text-ink/55 ring-1 ring-ink/[0.06] hover:text-base-action'
                    "
                    @click="scrollTo(section.id)"
                >
                    <i :class="section.icon" class="text-sm" aria-hidden="true" />
                    {{ section.label }}
                </button>
            </nav>

            <div class="space-y-5 sm:space-y-6">
                <section
                    id="section-profile"
                    class="profile-panel scroll-mt-28 overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]"
                >
                    <div class="section-header relative overflow-hidden px-5 py-5 sm:px-8 sm:py-6">
                        <div
                            class="pointer-events-none absolute inset-0 bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427]"
                            aria-hidden="true"
                        />
                        <div
                            class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(45%_55%_at_100%_100%,rgba(255,106,61,0.14),transparent_50%)]"
                            aria-hidden="true"
                        />
                        <div class="relative flex items-start gap-3.5">
                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/12 text-lg text-white ring-1 ring-white/15"
                            >
                                <i class="ti ti-user-circle" aria-hidden="true" />
                            </span>
                            <div class="min-w-0">
                                <h2
                                    class="font-editorial text-xl font-semibold tracking-tight text-white sm:text-[1.35rem]"
                                >
                                    Your public presence
                                </h2>
                                <p class="mt-1 text-sm font-medium leading-relaxed text-white/65">
                                    Basics, expertise, and where clients can reach you — each in its own calm card.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6 lg:p-8">
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                            :profile="profile"
                            :locations="locations"
                            :trades="trades"
                            :skill-suggestions="skillSuggestions"
                            :credential-catalogue="credentialCatalogue"
                            :max-credentials="maxCredentials"
                            :show-header="false"
                        />
                    </div>
                </section>

                <section
                    id="section-messages"
                    class="profile-panel scroll-mt-28 overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]"
                >
                    <div class="section-header relative overflow-hidden px-5 py-5 sm:px-8 sm:py-6">
                        <div
                            class="pointer-events-none absolute inset-0 bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427]"
                            aria-hidden="true"
                        />
                        <div
                            class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(45%_55%_at_100%_100%,rgba(255,106,61,0.14),transparent_50%)]"
                            aria-hidden="true"
                        />
                        <div class="relative flex items-start gap-3.5">
                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/12 text-lg text-white ring-1 ring-white/15"
                            >
                                <i class="ti ti-brand-whatsapp" aria-hidden="true" />
                            </span>
                            <div class="min-w-0">
                                <h2
                                    class="font-editorial text-xl font-semibold tracking-tight text-white sm:text-[1.35rem]"
                                >
                                    Review messages
                                </h2>
                                <p class="mt-1 text-sm font-medium leading-relaxed text-white/65">
                                    The WhatsApp wording clients see when you ask for a review.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6 lg:p-8">
                        <UpdateReviewMessageSettingsForm
                            :profile="profile"
                            :defaults="reviewMessageDefaults"
                            :show-header="false"
                        />
                    </div>
                </section>

                <section
                    id="section-security"
                    class="profile-panel scroll-mt-28 overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]"
                >
                    <div class="section-header relative overflow-hidden px-5 py-5 sm:px-8 sm:py-6">
                        <div
                            class="pointer-events-none absolute inset-0 bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427]"
                            aria-hidden="true"
                        />
                        <div
                            class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(45%_55%_at_100%_100%,rgba(255,106,61,0.14),transparent_50%)]"
                            aria-hidden="true"
                        />
                        <div class="relative flex items-start gap-3.5">
                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/12 text-lg text-white ring-1 ring-white/15"
                            >
                                <i class="ti ti-lock" aria-hidden="true" />
                            </span>
                            <div class="min-w-0">
                                <h2
                                    class="font-editorial text-xl font-semibold tracking-tight text-white sm:text-[1.35rem]"
                                >
                                    Password & security
                                </h2>
                                <p class="mt-1 text-sm font-medium leading-relaxed text-white/65">
                                    Keep your account locked down with a strong password.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6 lg:p-8">
                        <UpdatePasswordForm :show-header="false" />
                    </div>
                </section>

                <section
                    id="section-danger"
                    class="profile-panel scroll-mt-28 overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-coral/15"
                >
                    <div
                        class="flex items-start gap-3.5 border-b border-coral/10 bg-gradient-to-r from-coral/[0.08] to-white px-5 py-5 sm:px-8 sm:py-6"
                    >
                        <span
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white text-lg text-coral shadow-sm ring-1 ring-coral/20"
                        >
                            <i class="ti ti-alert-triangle" aria-hidden="true" />
                        </span>
                        <div class="min-w-0">
                            <h2
                                class="font-editorial text-xl font-semibold tracking-tight text-ink sm:text-[1.35rem]"
                            >
                                Danger zone
                            </h2>
                            <p class="mt-1 text-sm font-medium leading-relaxed text-ink/50">
                                Permanent actions — download anything you need before you delete.
                            </p>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6 lg:p-8">
                        <DeleteUserForm :show-header="false" />
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import UserAvatar from '@/Components/App/UserAvatar.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdateReviewMessageSettingsForm from './Partials/UpdateReviewMessageSettingsForm.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    mustVerifyEmail: { type: Boolean, default: false },
    status: { type: String, default: null },
    profile: { type: Object, required: true },
    locations: { type: Object, default: () => ({}) },
    trades: { type: Array, default: () => [] },
    skillSuggestions: { type: Array, default: () => [] },
    credentialCatalogue: { type: Array, default: () => [] },
    maxCredentials: { type: Number, default: 6 },
    reviewMessageDefaults: {
        type: Object,
        default: () => ({
            invite: '',
            reminder: '',
            max_length: 700,
            default_reminder_days: 3,
        }),
    },
});

const sections = [
    { id: 'section-profile', label: 'Presence', icon: 'ti ti-user-circle' },
    { id: 'section-messages', label: 'Messages', icon: 'ti ti-brand-whatsapp' },
    { id: 'section-security', label: 'Security', icon: 'ti ti-lock' },
    { id: 'section-danger', label: 'Danger', icon: 'ti ti-alert-triangle' },
];

const activeSection = ref('section-profile');

const displayName = computed(() => {
    const business = String(props.profile.business_name || '').trim();
    if (business) return business;
    const name = [props.profile.first_name, props.profile.last_name].filter(Boolean).join(' ').trim();
    return name || 'Your profile';
});

const initials = computed(() => {
    const first = String(props.profile.first_name || '').trim();
    const last = String(props.profile.last_name || '').trim();
    const business = String(props.profile.business_name || '').trim();
    if (first || last) {
        return `${first.charAt(0)}${last.charAt(0)}`.toUpperCase() || 'I';
    }
    return business.slice(0, 2).toUpperCase() || 'I';
});

const scrollTo = (id) => {
    activeSection.value = id;
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

let observer;

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            const visible = entries
                .filter((e) => e.isIntersecting)
                .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
            if (visible?.target?.id) {
                activeSection.value = visible.target.id;
            }
        },
        { rootMargin: '-20% 0px -55% 0px', threshold: [0.15, 0.4] },
    );
    sections.forEach((s) => {
        const el = document.getElementById(s.id);
        if (el) observer.observe(el);
    });
});

onUnmounted(() => {
    observer?.disconnect();
});
</script>

<style scoped>
.profile-hero {
    animation: rise 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.profile-nav {
    animation: rise 0.55s 0.06s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.profile-panel {
    animation: rise 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.profile-panel:nth-of-type(1) {
    animation-delay: 0.08s;
}
.profile-panel:nth-of-type(2) {
    animation-delay: 0.12s;
}
.profile-panel:nth-of-type(3) {
    animation-delay: 0.16s;
}
.profile-panel:nth-of-type(4) {
    animation-delay: 0.2s;
}

@keyframes rise {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
