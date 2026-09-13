<template>
    <Head title="Contact" />

    <div class="min-h-dvh bg-pale text-ink">
        <PublicTopBar :can-login="canLogin" :can-register="canRegister" />

        <main class="pb-16 sm:pb-24">
            <div class="mx-auto max-w-5xl px-4 pt-8 sm:px-8 sm:pt-12">
                <section
                    class="relative overflow-hidden rounded-[1.5rem] bg-[#0B1F3A] px-5 py-10 shadow-premium-ink sm:rounded-[1.75rem] sm:px-10 sm:py-14"
                >
                    <div
                        class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.1),transparent_55%),radial-gradient(45%_55%_at_100%_100%,rgba(255,106,61,0.14),transparent_50%)]"
                        aria-hidden="true"
                    />
                    <div class="relative max-w-2xl">
                        <p
                            class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/50"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-coral" aria-hidden="true" />
                            Support
                        </p>
                        <h1
                            class="mt-4 font-editorial text-[2.15rem] font-semibold leading-[1.08] tracking-tight text-white sm:text-[3rem]"
                        >
                            We’re here when the work needs a hand
                        </h1>
                        <p
                            class="mt-4 max-w-xl text-sm font-medium leading-relaxed text-white/70 sm:text-base"
                        >
                            Account help, billing, partnerships, or press — reach the Kraftrack team.
                            We support artisans and clients across Nigeria, with replies in West Africa
                            Time (WAT).
                        </p>
                    </div>
                </section>
            </div>

            <!-- Channels -->
            <section class="mx-auto mt-10 max-w-5xl px-4 sm:mt-14 sm:px-8">
                <ul class="grid gap-3 sm:grid-cols-3 sm:gap-4">
                    <li
                        v-for="channel in channels"
                        :key="channel.title"
                        class="rounded-[1.35rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-6"
                    >
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-tint text-deep"
                        >
                            <i :class="channel.icon" class="text-lg" aria-hidden="true" />
                        </span>
                        <h2 class="mt-4 text-sm font-bold tracking-tight text-ink">
                            {{ channel.title }}
                        </h2>
                        <p class="mt-1.5 text-sm font-medium leading-relaxed text-ink/50">
                            {{ channel.body }}
                        </p>
                        <a
                            v-if="channel.href"
                            :href="channel.href"
                            class="mt-4 inline-flex items-center gap-1.5 text-sm font-bold text-base-action transition-colors hover:text-base-hover"
                        >
                            {{ channel.cta }}
                            <i class="ti ti-arrow-up-right text-base" aria-hidden="true" />
                        </a>
                        <p v-else class="mt-4 text-sm font-bold text-ink">
                            {{ channel.cta }}
                        </p>
                    </li>
                </ul>
            </section>

            <!-- Form + sidebar -->
            <section class="mx-auto mt-12 max-w-5xl px-4 sm:mt-16 sm:px-8">
                <div class="grid gap-8 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)] lg:gap-10 lg:items-start">
                    <div
                        class="rounded-[1.5rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-8"
                    >
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-base-action">
                            Send a message
                        </p>
                        <h2
                            class="mt-2 font-editorial text-[1.45rem] font-semibold tracking-tight text-ink sm:text-[1.75rem]"
                        >
                            Tell us what’s going on
                        </h2>
                        <p class="mt-2 text-sm font-medium leading-relaxed text-ink/50">
                            Include the email on your Kraftrack account if this is about login, credits,
                            or a public page. We usually reply within 1–2 working days.
                        </p>

                        <div
                            v-if="flashSuccess"
                            class="mt-5 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-200/80"
                            role="status"
                        >
                            {{ flashSuccess }}
                        </div>

                        <form class="mt-6 space-y-4" @submit.prevent="submit">
                            <FormTextInput
                                v-model="form.name"
                                label="Full name"
                                name="name"
                                autocomplete="name"
                                placeholder="Your name"
                                required
                                :error="form.errors.name"
                            />

                            <FormTextInput
                                v-model="form.email"
                                type="email"
                                label="Email"
                                name="email"
                                autocomplete="email"
                                placeholder="you@example.com"
                                required
                                :error="form.errors.email"
                            />

                            <FormTextInput
                                v-model="form.phone"
                                type="tel"
                                label="Phone (optional)"
                                hint="Nigerian number helps if we need a quick follow-up."
                                name="phone"
                                autocomplete="tel"
                                placeholder="e.g. 0803 000 0000"
                                :error="form.errors.phone"
                            />

                            <FormSelect
                                v-model="form.topic"
                                label="Topic"
                                :options="topicOptions"
                                placeholder="Choose a topic"
                                :error="form.errors.topic"
                            />

                            <FormTextarea
                                v-model="form.message"
                                label="Message"
                                name="message"
                                rows="5"
                                placeholder="What do you need help with? Include links or job details if useful."
                                required
                                :error="form.errors.message"
                            />

                            <FormButton
                                type="submit"
                                variant="primary"
                                label="Send message"
                                loading-label="Sending…"
                                :loading="form.processing"
                                class="w-full sm:w-auto"
                            />
                        </form>
                    </div>

                    <aside class="space-y-4">
                        <div
                            class="rounded-[1.35rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-6"
                        >
                            <h3 class="text-sm font-bold tracking-tight text-ink">
                                Before you write
                            </h3>
                            <ul class="mt-4 space-y-3">
                                <li
                                    v-for="tip in tips"
                                    :key="tip"
                                    class="flex gap-3 text-sm font-medium leading-relaxed text-ink/55"
                                >
                                    <i
                                        class="ti ti-check mt-0.5 shrink-0 text-base text-base-action"
                                        aria-hidden="true"
                                    />
                                    <span>{{ tip }}</span>
                                </li>
                            </ul>
                        </div>

                        <div
                            class="overflow-hidden rounded-[1.35rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] p-5 text-white shadow-premium-ink sm:p-6"
                        >
                            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-white/50">
                                Prefer self-serve?
                            </p>
                            <p class="mt-2 text-sm font-medium leading-relaxed text-white/70">
                                Many answers about pricing, reviews, and your page are already in the FAQ.
                            </p>
                            <Link
                                :href="route('faq')"
                                class="mt-5 inline-flex items-center gap-1.5 rounded-2xl bg-white px-4 py-2.5 text-sm font-bold text-deep transition-opacity hover:opacity-90"
                            >
                                Browse FAQ
                                <i class="ti ti-arrow-right text-base" aria-hidden="true" />
                            </Link>
                        </div>

                        <div
                            class="rounded-[1.35rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-6"
                        >
                            <h3 class="text-sm font-bold tracking-tight text-ink">Hours</h3>
                            <p class="mt-2 text-sm font-medium leading-relaxed text-ink/50">
                                Monday–Friday, 9:00–17:00 WAT. Weekends we still read urgent account
                                lockouts, but general replies may wait until the next working day.
                            </p>
                        </div>
                    </aside>
                </div>
            </section>
        </main>

        <SiteFooter :can-register="canRegister" />
    </div>
</template>

<script setup>
import FormButton from '@/Components/Form/FormButton.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import PublicTopBar from '@/Components/Marketing/PublicTopBar.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    canLogin: { type: Boolean, default: true },
    canRegister: { type: Boolean, default: true },
    contactEmail: { type: String, default: 'hello@kraftrack.com' },
});

const page = usePage();
const flashSuccess = computed(() => {
    const toast = page.props.flash?.toast;
    return toast?.type === 'success' ? toast.message : null;
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    topic: '',
    message: '',
});

const topicOptions = [
    { value: 'account', label: 'Account & login' },
    { value: 'billing', label: 'Billing & credits' },
    { value: 'reviews', label: 'Reviews & public page' },
    { value: 'partnership', label: 'Partnerships' },
    { value: 'press', label: 'Press & media' },
    { value: 'careers', label: 'Careers' },
    { value: 'other', label: 'Something else' },
];

const channels = [
    {
        icon: 'ti ti-mail',
        title: 'Email',
        body: 'Best for account issues, billing, and anything that needs a paper trail.',
        cta: 'hello@kraftrack.com',
        href: 'mailto:hello@kraftrack.com',
    },
    {
        icon: 'ti ti-clock',
        title: 'Response time',
        body: 'Most messages get a human reply within 1–2 working days (WAT).',
        cta: 'Mon–Fri · Nigeria',
        href: null,
    },
    {
        icon: 'ti ti-map-pin',
        title: 'Where we focus',
        body: 'Built for tradespeople and clients across Nigeria — Lagos to the states, mobile-first.',
        cta: 'Nationwide product',
        href: null,
    },
];

const tips = [
    'For login problems, use the email you registered with.',
    'For credits or Paystack receipts, include the payment reference if you have it.',
    'For a public page issue, paste the /p/… link.',
];

const submit = () => {
    form.post(route('contact.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('message', 'topic'),
    });
};
</script>
