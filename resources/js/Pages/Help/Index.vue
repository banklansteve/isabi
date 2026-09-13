<template>
    <Head title="Help & support" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-5xl">
            <section
                class="help-hero relative mb-6 overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-7 shadow-premium-ink sm:mb-8 sm:px-7 sm:py-8"
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
                    <div class="min-w-0">
                        <p
                            class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/55"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-coral" aria-hidden="true" />
                            Support
                        </p>
                        <h1
                            class="mt-2.5 font-editorial text-[2rem] font-semibold leading-[1.1] tracking-tight text-white sm:text-[2.35rem]"
                        >
                            Help & support
                        </h1>
                        <p class="mt-2 max-w-md text-sm font-medium leading-relaxed text-white/65">
                            Concise answers about your page, credits, reviews, and work log — plus a
                            direct line to the Kraftrack team.
                        </p>
                    </div>
                    <Link
                        :href="route('help.chat')"
                        class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-coral px-5 py-3 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(255,106,61,0.55)] transition-colors hover:bg-coral-deep"
                    >
                        <i class="ti ti-message-circle-2" aria-hidden="true" />
                        Start a chat
                    </Link>
                </div>
            </section>

            <div class="grid gap-5 lg:grid-cols-[1.45fr_1fr]">
                <div class="min-w-0 space-y-4">
                    <label class="relative block">
                        <i
                            class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/35"
                            aria-hidden="true"
                        />
                        <input
                            v-model="query"
                            type="search"
                            placeholder="Search help topics"
                            class="w-full rounded-2xl border-0 bg-white py-3.5 ps-10 pe-4 text-sm font-medium text-ink shadow-premium outline-none ring-1 ring-ink/[0.06] placeholder:text-ink/35 focus:ring-2 focus:ring-base/20"
                        />
                    </label>

                    <nav
                        v-if="!query.trim()"
                        class="flex gap-2 overflow-x-auto pb-1"
                        aria-label="Help sections"
                    >
                        <a
                            v-for="group in groups"
                            :key="group.id"
                            :href="`#${group.id}`"
                            class="tap-target inline-flex shrink-0 items-center gap-2 rounded-full bg-white px-3.5 py-2 text-xs font-bold text-ink/70 shadow-sm ring-1 ring-ink/[0.06] transition-colors hover:text-deep"
                        >
                            <i :class="group.icon" aria-hidden="true" />
                            {{ group.title }}
                        </a>
                    </nav>

                    <FaqAccordion
                        v-if="!query.trim()"
                        :groups="groups"
                    />

                    <section
                        v-else
                        class="overflow-hidden rounded-[1.35rem] bg-white shadow-premium ring-1 ring-ink/[0.06]"
                    >
                        <p
                            v-if="!searchResults.length"
                            class="px-5 py-8 text-sm font-medium text-ink/45"
                        >
                            No matching topics. Try chat instead.
                        </p>
                        <div
                            v-for="(item, index) in searchResults"
                            :id="item.id"
                            :key="item.id"
                            class="scroll-mt-28 px-5 py-4 sm:px-6"
                            :class="index > 0 ? 'border-t border-ink/[0.06]' : ''"
                        >
                            <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink/35">
                                {{ item.groupTitle }}
                            </p>
                            <h3 class="mt-1 text-sm font-bold text-ink">
                                {{ item.question }}
                            </h3>
                            <p class="mt-2 text-sm font-medium leading-relaxed text-ink/55">
                                {{ item.answer }}
                            </p>
                        </div>
                    </section>
                </div>

                <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                    <div
                        class="rounded-[1.5rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-6"
                    >
                        <h2 class="font-editorial text-lg font-semibold tracking-tight text-ink">
                            Popular right now
                        </h2>
                        <ul class="mt-3 divide-y divide-ink/[0.05]">
                            <li
                                v-for="topic in popular"
                                :key="topic.id"
                            >
                                <a
                                    :href="`#${topic.id}`"
                                    class="tap-target flex items-center justify-between gap-3 py-3 text-sm font-semibold text-ink transition-colors hover:text-base-action"
                                    @click.prevent="jumpTo(topic.id)"
                                >
                                    <span>{{ topic.question }}</span>
                                    <i class="ti ti-chevron-right text-ink/30" aria-hidden="true" />
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div
                        class="rounded-[1.5rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-6"
                    >
                        <h2 class="font-editorial text-lg font-semibold tracking-tight text-ink">
                            Contact the team
                        </h2>
                        <p class="mt-2 text-sm font-medium leading-relaxed text-ink/50">
                            Prefer a human? Start an in-app chat — we usually reply within a few hours
                            on business days.
                        </p>
                        <Link
                            :href="route('help.chat')"
                            class="tap-target mt-5 flex w-full items-center justify-center gap-2 rounded-2xl bg-base-action px-4 py-3 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                        >
                            <i class="ti ti-message-circle-2" aria-hidden="true" />
                            Chat with support
                        </Link>
                        <a
                            href="mailto:hello@kraftrack.com"
                            class="tap-target mt-2 flex w-full items-center justify-center gap-2 rounded-2xl border border-ink/10 bg-white px-4 py-3 text-sm font-bold text-ink/70 transition-colors hover:border-ink/20 hover:text-ink"
                        >
                            <i class="ti ti-mail" aria-hidden="true" />
                            hello@kraftrack.com
                        </a>
                    </div>

                    <div
                        class="rounded-[1.5rem] bg-white p-5 shadow-premium ring-1 ring-ink/[0.06] sm:p-6"
                    >
                        <h2 class="font-editorial text-lg font-semibold tracking-tight text-ink">
                            More reading
                        </h2>
                        <ul class="mt-3 space-y-1">
                            <li>
                                <Link
                                    :href="route('faq')"
                                    class="tap-target flex items-center justify-between gap-2 rounded-xl px-2 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-pale"
                                >
                                    Full FAQ
                                    <i class="ti ti-chevron-right text-ink/30" aria-hidden="true" />
                                </Link>
                            </li>
                            <li>
                                <Link
                                    :href="route('how-it-works')"
                                    class="tap-target flex items-center justify-between gap-2 rounded-xl px-2 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-pale"
                                >
                                    How it works
                                    <i class="ti ti-chevron-right text-ink/30" aria-hidden="true" />
                                </Link>
                            </li>
                            <li>
                                <Link
                                    :href="route('tokens.index')"
                                    class="tap-target flex items-center justify-between gap-2 rounded-xl px-2 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-pale"
                                >
                                    Tokens & plan
                                    <i class="ti ti-chevron-right text-ink/30" aria-hidden="true" />
                                </Link>
                            </li>
                        </ul>
                    </div>
                </aside>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import FaqAccordion from '@/Components/Help/FaqAccordion.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { helpGroups, popularTopics, searchHelpTopics } from '@/Data/helpTopics';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const query = ref('');
const groups = helpGroups;
const popular = popularTopics('auth', 5);

const searchResults = computed(() => searchHelpTopics(query.value));

const jumpTo = (id) => {
    query.value = '';
    if (window.location.hash !== `#${id}`) {
        window.location.hash = id;
    } else {
        window.dispatchEvent(new Event('hashchange'));
    }
    requestAnimationFrame(() => {
        document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
};
</script>

<style scoped>
.help-hero {
    animation: rise 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes rise {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
