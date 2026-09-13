<template>
    <Head :title="title" />

    <div class="min-h-dvh bg-pale text-ink">
        <PublicTopBar :can-login="canLogin" :can-register="canRegister" />

        <main class="pb-16 sm:pb-24">
            <div class="mx-auto max-w-6xl px-4 pt-6 sm:px-8 sm:pt-10">
                <section
                    class="relative overflow-hidden rounded-[1.5rem] bg-[#0B1F3A] px-5 py-9 shadow-premium-ink sm:rounded-[1.75rem] sm:px-10 sm:py-12"
                >
                    <div
                        class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_12%_0%,rgba(255,255,255,0.1),transparent_55%),radial-gradient(50%_60%_at_100%_100%,rgba(255,106,61,0.12),transparent_50%)]"
                        aria-hidden="true"
                    />
                    <div class="relative max-w-3xl">
                        <p
                            class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-[#A8B0BC]"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-coral" aria-hidden="true" />
                            {{ eyebrow }}
                        </p>
                        <h1
                            class="mt-3 font-editorial text-[2.05rem] font-semibold leading-[1.1] tracking-tight text-white sm:text-[2.6rem]"
                        >
                            {{ title }}
                        </h1>
                        <p
                            class="mt-3 max-w-2xl text-sm font-medium leading-relaxed text-[#D8DCE3] sm:text-base sm:leading-relaxed"
                        >
                            {{ summary }}
                        </p>
                        <p
                            v-if="updated"
                            class="mt-5 text-[11px] font-semibold uppercase tracking-[0.14em] text-[#8B93A1]"
                        >
                            Last updated · {{ updated }}
                        </p>
                    </div>
                </section>
            </div>

            <article class="mx-auto mt-8 max-w-6xl px-4 sm:mt-10 sm:px-8">
                <div
                    class="rounded-[1.5rem] bg-white px-5 py-7 shadow-premium ring-1 ring-ink/[0.06] sm:px-10 sm:py-11"
                >
                    <nav
                        v-if="sections.length > 3"
                        class="mb-8 rounded-2xl bg-[#F4F6FA] p-4 sm:p-5"
                        aria-label="On this page"
                    >
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/40">
                            On this page
                        </p>
                        <ol class="mt-3 grid gap-1.5 sm:grid-cols-2">
                            <li v-for="(section, index) in sections" :key="section.heading">
                                <a
                                    :href="`#section-${index}`"
                                    class="text-sm font-semibold text-ink/60 transition-colors hover:text-ink"
                                >
                                    {{ section.heading }}
                                </a>
                            </li>
                        </ol>
                    </nav>

                    <div class="space-y-9 sm:space-y-11">
                        <section
                            v-for="(section, index) in sections"
                            :id="`section-${index}`"
                            :key="section.heading"
                            class="scroll-mt-28"
                        >
                            <h2
                                class="font-editorial text-[1.2rem] font-semibold tracking-tight text-ink sm:text-[1.4rem]"
                            >
                                {{ section.heading }}
                            </h2>
                            <div class="mt-3 space-y-3.5">
                                <p
                                    v-for="(paragraph, pIndex) in section.paragraphs"
                                    :key="pIndex"
                                    class="text-sm font-medium leading-relaxed text-ink/60 sm:text-[0.97rem] sm:leading-relaxed"
                                >
                                    {{ paragraph }}
                                </p>
                            </div>
                        </section>
                    </div>

                    <div
                        class="mt-10 border-t border-ink/[0.06] pt-6 text-sm font-medium leading-relaxed text-ink/45"
                    >
                        Questions about this policy?
                        <a
                            href="mailto:hello@kraftrack.com"
                            class="font-bold text-ink hover:underline"
                        >
                            hello@kraftrack.com
                        </a>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-2 sm:gap-3">
                    <Link
                        v-for="link in relatedLinks"
                        :key="link.route"
                        :href="route(link.route)"
                        class="tap-target inline-flex items-center rounded-full bg-white px-3.5 py-2 text-xs font-bold text-ink/55 ring-1 ring-ink/[0.06] transition-colors hover:text-ink"
                    >
                        {{ link.label }}
                    </Link>
                </div>
            </article>
        </main>

        <SiteFooter :can-register="canRegister" />
    </div>
</template>

<script setup>
import PublicTopBar from '@/Components/Marketing/PublicTopBar.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    eyebrow: { type: String, default: 'Legal' },
    summary: { type: String, required: true },
    updated: { type: String, default: '' },
    sections: {
        type: Array,
        default: () => [],
    },
    slug: { type: String, default: '' },
    canLogin: { type: Boolean, default: true },
    canRegister: { type: Boolean, default: true },
});

const relatedLinks = computed(() => {
    const all = [
        { route: 'terms', label: 'Terms of use' },
        { route: 'privacy', label: 'Privacy' },
        { route: 'cookies', label: 'Cookies' },
        { route: 'acceptable-use', label: 'Acceptable use' },
    ];
    return all.filter((link) => link.route !== props.slug);
});
</script>
