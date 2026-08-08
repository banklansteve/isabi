<template>
    <Head :title="`${roleLabel} · Admin`" />

    <div class="min-h-dvh bg-pale text-ink">
        <header class="border-b border-ink/10 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-coral-deep">
                        Admin
                    </p>
                    <h1 class="font-display text-xl font-bold tracking-tight text-ink">
                        {{ roleLabel }}
                    </h1>
                </div>
                <div class="flex items-center gap-4">
                    <Link
                        v-if="$page.props.auth?.user?.is_super_admin"
                        :href="route('admin.activity')"
                        class="tap-target inline-flex items-center text-sm font-semibold text-ink/60 transition-colors hover:text-ink"
                    >
                        Activity log
                    </Link>
                    <Link
                        :href="route('home')"
                        class="tap-target inline-flex items-center text-sm font-semibold text-ink/60 transition-colors hover:text-ink"
                    >
                        View site
                    </Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-5 py-10 sm:px-8">
            <div class="rounded-[1.5rem] bg-white p-6 shadow-premium ring-1 ring-ink/[0.06] sm:p-8">
                <h2 class="font-display text-2xl font-bold tracking-tight text-ink">
                    Welcome back
                </h2>
                <p class="mt-3 max-w-2xl text-sm font-medium leading-relaxed text-ink/60 sm:text-base">
                    {{ roleDescription }}
                </p>

                <div class="mt-8">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-ink/40">
                        Current abilities
                    </p>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        <li
                            v-for="ability in abilities"
                            :key="ability"
                            class="rounded-full bg-tint px-3 py-1 text-xs font-semibold text-deep"
                        >
                            {{ ability }}
                        </li>
                        <li
                            v-if="abilities.length === 0"
                            class="text-sm font-medium text-ink/45"
                        >
                            No admin abilities on this role.
                        </li>
                    </ul>
                </div>

                <p class="mt-8 text-sm font-medium text-ink/45">
                    This is the staff home shell. Module screens (users, content, requests, billing)
                    will plug in under
                    <code class="rounded bg-pale px-1.5 py-0.5 text-ink/70">/admin/*</code>
                    with
                    <code class="rounded bg-pale px-1.5 py-0.5 text-ink/70">role</code>
                    /
                    <code class="rounded bg-pale px-1.5 py-0.5 text-ink/70">ability</code>
                    middleware.
                </p>
            </div>
        </main>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    roleLabel: {
        type: String,
        required: true,
    },
    roleDescription: {
        type: String,
        required: true,
    },
    abilities: {
        type: Array,
        default: () => [],
    },
});
</script>
