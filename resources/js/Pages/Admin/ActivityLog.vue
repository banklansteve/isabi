<template>
    <Head title="Activity log" />

    <div class="min-h-dvh bg-pale font-app text-ink">
        <header class="border-b border-ink/10 bg-white">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-5 py-4 sm:px-8">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-coral-deep">
                        Super Admin
                    </p>
                    <h1 class="text-xl font-semibold tracking-tight text-ink">
                        Activity log
                    </h1>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.dashboard')"
                        class="tap-target inline-flex items-center text-sm font-semibold text-ink/55 transition-colors hover:text-ink"
                    >
                        Admin home
                    </Link>
                    <Link
                        :href="route('home')"
                        class="tap-target inline-flex items-center text-sm font-semibold text-ink/55 transition-colors hover:text-ink"
                    >
                        View site
                    </Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-5 py-8 sm:px-8">
            <form
                class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center"
                @submit.prevent="applyFilters"
            >
                <div class="relative min-w-0 flex-1">
                    <i class="ti ti-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink/30" aria-hidden="true" />
                    <input
                        v-model="form.q"
                        type="search"
                        placeholder="Search summaries, actions, people…"
                        class="w-full rounded-xl border border-ink/10 bg-white py-2.5 ps-10 pe-4 text-sm font-medium text-ink outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                    />
                </div>
                <select
                    v-model="form.action"
                    class="rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium text-ink outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                >
                    <option value="">All actions</option>
                    <option v-for="action in actions" :key="action" :value="action">
                        {{ action }}
                    </option>
                </select>
                <button
                    type="submit"
                    class="tap-target rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.5)] transition-colors hover:bg-base-hover"
                >
                    Filter
                </button>
            </form>

            <div class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.06]">
                <ul v-if="logs.data.length" class="divide-y divide-ink/10">
                    <li
                        v-for="log in logs.data"
                        :key="log.id"
                        class="flex gap-3.5 px-5 py-4 sm:px-6"
                    >
                        <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-pale text-base text-ink/45">
                            <i :class="log.icon" aria-hidden="true" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                                <p class="text-sm font-semibold tracking-tight text-ink">
                                    {{ log.title }}
                                </p>
                                <p class="text-[11px] font-medium text-ink/35" :title="log.created_at_human">
                                    {{ log.relative }}
                                </p>
                            </div>
                            <p class="mt-0.5 text-sm font-medium leading-relaxed text-ink/55">
                                {{ log.summary }}
                            </p>
                            <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[11px] font-medium text-ink/35">
                                <span v-if="log.user">{{ log.user.name }} · {{ log.user.email }}</span>
                                <span v-else>Guest / anonymous</span>
                                <span v-if="log.ip_address">IP {{ log.ip_address }}</span>
                                <span class="rounded bg-pale px-1.5 py-0.5 font-mono text-ink/45">{{ log.action }}</span>
                            </div>
                        </div>
                    </li>
                </ul>
                <div v-else class="px-6 py-16 text-center">
                    <p class="text-sm font-semibold text-ink">No activity yet</p>
                    <p class="mt-1 text-sm font-medium text-ink/45">
                        Sign-ins, cookie choices, and other actions will appear here.
                    </p>
                </div>
            </div>

            <div
                v-if="logs.prev_page_url || logs.next_page_url"
                class="mt-6 flex items-center justify-between gap-3"
            >
                <Link
                    v-if="logs.prev_page_url"
                    :href="logs.prev_page_url"
                    class="text-sm font-semibold text-base hover:text-deep"
                    preserve-scroll
                >
                    Previous
                </Link>
                <span v-else />
                <p class="text-xs font-medium text-ink/40">
                    Page {{ logs.current_page }} of {{ logs.last_page }}
                </p>
                <Link
                    v-if="logs.next_page_url"
                    :href="logs.next_page_url"
                    class="text-sm font-semibold text-base hover:text-deep"
                    preserve-scroll
                >
                    Next
                </Link>
                <span v-else />
            </div>
        </main>
    </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    actions: { type: Array, default: () => [] },
});

const form = reactive({
    q: props.filters.q || '',
    action: props.filters.action || '',
});

const applyFilters = () => {
    router.get(route('admin.activity'), {
        q: form.q || undefined,
        action: form.action || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};
</script>
