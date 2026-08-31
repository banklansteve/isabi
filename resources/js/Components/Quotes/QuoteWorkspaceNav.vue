<template>
    <div class="flex flex-wrap gap-2">
        <Link
            :href="route('quotes.requests.index')"
            class="tap-target inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-bold transition-all duration-200"
            :class="
                active === 'requests'
                    ? 'bg-base-action text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.45)]'
                    : 'bg-white text-ink/60 ring-1 ring-ink/10 hover:bg-tint hover:text-base-action'
            "
        >
            <i class="ti ti-inbox text-base" aria-hidden="true" />
            Quote requests
            <span
                v-if="requestCount > 0"
                class="rounded-full px-2 py-0.5 text-[11px] font-extrabold tabular-nums"
                :class="active === 'requests' ? 'bg-white/15 text-white' : 'bg-tint text-base-action'"
            >
                {{ requestCount }}
            </span>
        </Link>
        <Link
            :href="route('quotes.sent.index')"
            class="tap-target inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-bold transition-all duration-200"
            :class="
                active === 'sent'
                    ? 'bg-base-action text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.45)]'
                    : 'bg-white text-ink/60 ring-1 ring-ink/10 hover:bg-tint hover:text-base-action'
            "
        >
            <i class="ti ti-file-invoice text-base" aria-hidden="true" />
            My quotes
            <span
                v-if="quoteCount > 0"
                class="rounded-full px-2 py-0.5 text-[11px] font-extrabold tabular-nums"
                :class="active === 'sent' ? 'bg-white/15 text-white' : 'bg-tint text-base-action'"
            >
                {{ quoteCount }}
            </span>
        </Link>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    active: { type: String, required: true },
    requestCount: { type: Number, default: 0 },
    quoteCount: { type: Number, default: 0 },
});
</script>
