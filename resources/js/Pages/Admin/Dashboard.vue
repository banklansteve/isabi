<template>
    <Head :title="`${roleLabel} · Admin`" />

    <AdminChrome :title="roleLabel" />
        <div class="rounded-[1.5rem] bg-white p-6 shadow-premium ring-1 ring-ink/[0.06] sm:p-8">
            <h2 class="font-display text-2xl font-bold tracking-tight text-ink">
                Welcome back
            </h2>
            <p class="mt-3 max-w-2xl text-sm font-medium leading-relaxed text-ink/60 sm:text-base">
                {{ roleDescription }}
            </p>

            <div v-if="duties.length" class="mt-8">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-ink/40">
                    Current duties
                </p>
                <ul class="mt-3 flex flex-wrap gap-2">
                    <li
                        v-for="duty in duties"
                        :key="duty.slug"
                        class="rounded-full bg-tint px-3 py-1 text-xs font-semibold text-deep"
                    >
                        {{ duty.name }}
                    </li>
                </ul>
            </div>

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
                Module screens will be built next. Routes are live under
                <code class="rounded bg-pale px-1.5 py-0.5 text-ink/70">/admin/*</code>.
            </p>
        </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head } from '@inertiajs/vue3';

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
    duties: {
        type: Array,
        default: () => [],
    },
});
</script>
