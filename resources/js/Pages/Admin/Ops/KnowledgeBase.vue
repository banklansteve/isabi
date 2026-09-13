<template>
    <Head title="Knowledge base" />

    <AdminChrome title="Knowledge base" eyebrow="Growth &amp; lifecycle" />

    <div class="space-y-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <h1 class="font-editorial text-[1.6rem] font-semibold leading-tight tracking-tight text-ink sm:text-[1.9rem]">
                    Knowledge base upkeep
                </h1>
                <p class="mt-1 max-w-2xl text-[13px] font-medium leading-relaxed text-ink/50">
                    Spot FAQ gaps from recurring support questions and keep canned responses sharp so support stays fast.
                </p>
            </div>
            <Link
                v-if="can_edit_templates"
                :href="route('admin.support.templates')"
                class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] hover:bg-base-hover"
            >
                <i class="ti ti-pencil-plus" aria-hidden="true" /> Manage templates
            </Link>
        </header>

        <section>
            <div class="mb-2.5 flex items-center gap-2">
                <i class="ti ti-alert-triangle text-amber-500" aria-hidden="true" />
                <h2 class="text-sm font-bold text-ink">FAQ gaps</h2>
                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700">
                    {{ gaps.length }}
                </span>
            </div>
            <p class="mb-3 text-[12px] font-medium text-ink/45">
                Topics asked 3+ times in the last 90 days with no canned response yet.
            </p>

            <div v-if="gaps.length" class="grid gap-2.5 sm:grid-cols-2">
                <article
                    v-for="gap in gaps"
                    :key="gap.topic"
                    class="flex items-center justify-between gap-3 rounded-2xl bg-white p-4 shadow-premium ring-1 ring-amber-200/70"
                >
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-ink">{{ gap.label }}</p>
                        <p class="mt-0.5 text-[12px] font-semibold text-amber-700">
                            {{ gap.total }} question{{ gap.total === 1 ? '' : 's' }} · no template
                        </p>
                    </div>
                    <Link
                        v-if="can_edit_templates"
                        :href="route('admin.support.templates')"
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-amber-500 px-3 py-2 text-[12px] font-bold text-white hover:bg-amber-600"
                    >
                        <i class="ti ti-plus text-sm" aria-hidden="true" /> Add
                    </Link>
                </article>
            </div>
            <div v-else class="rounded-2xl bg-white p-6 text-center shadow-premium ring-1 ring-ink/[0.05]">
                <i class="ti ti-circle-check text-2xl text-emerald-500" aria-hidden="true" />
                <p class="mt-2 text-sm font-bold text-ink">No gaps right now</p>
                <p class="mt-0.5 text-[12px] font-medium text-ink/45">Recurring topics all have a canned response.</p>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <div>
                <h2 class="mb-2.5 text-sm font-bold text-ink">Top question topics · 90 days</h2>
                <div v-if="topics.length" class="space-y-2">
                    <div
                        v-for="topic in topics"
                        :key="topic.topic"
                        class="flex items-center gap-3 rounded-xl bg-white px-3.5 py-2.5 shadow-premium ring-1 ring-ink/[0.05]"
                    >
                        <span class="min-w-0 flex-1 truncate text-[13px] font-semibold text-ink">{{ topic.label }}</span>
                        <div class="h-1.5 w-24 overflow-hidden rounded-full bg-pale">
                            <span class="block h-full rounded-full bg-base-action" :style="{ width: barWidth(topic.total) }" />
                        </div>
                        <span class="w-8 shrink-0 text-right text-[12px] font-bold tabular-nums text-ink/50">{{ topic.total }}</span>
                    </div>
                </div>
                <p v-else class="rounded-2xl bg-white p-6 text-center text-[12px] font-medium text-ink/45 shadow-premium ring-1 ring-ink/[0.05]">
                    No tagged support questions yet.
                </p>
            </div>

            <div>
                <h2 class="mb-2.5 text-sm font-bold text-ink">Canned responses</h2>
                <div v-if="templates.length" class="space-y-2">
                    <article
                        v-for="template in templates"
                        :key="template.id"
                        class="rounded-xl bg-white p-3.5 shadow-premium ring-1 ring-ink/[0.05]"
                    >
                        <div class="flex items-center gap-2">
                            <p class="min-w-0 flex-1 truncate text-[13px] font-bold text-ink">{{ template.title }}</p>
                            <span
                                v-if="template.is_system"
                                class="rounded-full bg-pale px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-ink/40"
                            >
                                Built-in
                            </span>
                        </div>
                        <p class="mt-1 text-[12px] font-medium leading-relaxed text-ink/45">{{ template.body }}</p>
                    </article>
                </div>
                <p v-else class="rounded-2xl bg-white p-6 text-center text-[12px] font-medium text-ink/45 shadow-premium ring-1 ring-ink/[0.05]">
                    No canned responses saved yet.
                </p>
            </div>
        </section>
    </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    gaps: { type: Array, default: () => [] },
    topics: { type: Array, default: () => [] },
    templates: { type: Array, default: () => [] },
    can_edit_templates: { type: Boolean, default: false },
});

const maxTopic = computed(() => Math.max(1, ...props.topics.map((t) => t.total)));

const barWidth = (total) => `${Math.max(6, Math.round((total / maxTopic.value) * 100))}%`;
</script>
