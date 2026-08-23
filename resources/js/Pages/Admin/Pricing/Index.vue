<template>
    <Head title="Pricing" />

    <AdminChrome title="Pricing & plans" eyebrow="Single source of truth" />
        <div v-if="tab === 'history'" class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05]">
            <AdminEmpty
                v-if="!history.length"
                title="No versions yet"
                description="The first time you publish a change, it will appear here with a full audit trail."
                icon="ti ti-history"
            />
            <ul v-else class="divide-y divide-ink/[0.06]">
                <li v-for="row in history" :key="row.id" class="py-3">
                    <p class="text-sm font-bold text-ink">v{{ row.version }} · {{ row.summary }}</p>
                    <p class="text-[13px] text-ink/45">{{ row.author }} · {{ row.created_at }}</p>
                </li>
            </ul>
        </div>

        <form v-else class="space-y-4" @submit.prevent="save">
            <div class="grid gap-3 sm:grid-cols-3">
                <label class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05]">
                    <span class="text-[12px] font-bold uppercase tracking-wide text-ink/40">Free review links / month</span>
                    <input v-model.number="form.free.monthly_review_links" type="number" min="0" class="mt-2 w-full border-0 bg-transparent p-0 text-2xl font-extrabold outline-none" />
                </label>
                <label class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05]">
                    <span class="text-[12px] font-bold uppercase tracking-wide text-ink/40">Annual price (₦)</span>
                    <input v-model.number="form.annual.price" type="number" min="0" class="mt-2 w-full border-0 bg-transparent p-0 text-2xl font-extrabold outline-none" />
                </label>
                <label class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05]">
                    <span class="text-[12px] font-bold uppercase tracking-wide text-ink/40">Referral reward</span>
                    <input v-model.number="form.referral.credits_reward" type="number" min="0" class="mt-2 w-full border-0 bg-transparent p-0 text-2xl font-extrabold outline-none" />
                </label>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05]">
                <h3 class="text-sm font-bold text-ink">Per-action costs (tokens)</h3>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <label class="text-sm font-semibold text-ink/50">
                        Review link
                        <input v-model.number="form.credits.actions.review_link" type="number" class="mt-1 w-full rounded-xl border border-ink/10 px-3 py-2 text-ink" />
                    </label>
                    <label class="text-sm font-semibold text-ink/50">
                        QR download
                        <input v-model.number="form.credits.actions.qr_download" type="number" class="mt-1 w-full rounded-xl border border-ink/10 px-3 py-2 text-ink" />
                    </label>
                    <label class="text-sm font-semibold text-ink/50">
                        Vanity slug
                        <input v-model.number="form.credits.actions.vanity_slug" type="number" class="mt-1 w-full rounded-xl border border-ink/10 px-3 py-2 text-ink" />
                    </label>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-premium ring-1 ring-ink/[0.05]">
                <h3 class="text-sm font-bold text-ink">Credit packs</h3>
                <div v-for="(pack, i) in form.credits.packs" :key="pack.key" class="mt-3 grid gap-2 sm:grid-cols-4">
                    <input v-model="form.credits.packs[i].name" class="rounded-xl border border-ink/10 px-3 py-2 text-sm font-medium" />
                    <input v-model.number="form.credits.packs[i].credits" type="number" class="rounded-xl border border-ink/10 px-3 py-2 text-sm font-medium" />
                    <input v-model.number="form.credits.packs[i].price" type="number" class="rounded-xl border border-ink/10 px-3 py-2 text-sm font-medium" />
                    <p class="self-center text-[12px] font-medium text-ink/40">{{ pack.key }}</p>
                </div>
            </div>

            <button type="submit" class="rounded-xl bg-base-action px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-base-hover active:scale-[0.98] disabled:opacity-50" :disabled="form.processing">
                Publish pricing
            </button>
        </form>
</template>

<script setup>
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import { useAdminTabs } from '@/Composables/useAdminTabs';
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    pricing: { type: Object, required: true },
    history: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const { tab } = useAdminTabs({ tab: props.filters.tab || '' });

const form = useForm({
    free: { monthly_review_links: props.pricing.free?.monthly_review_links ?? 5 },
    annual: { price: props.pricing.annual?.price ?? 0 },
    referral: { credits_reward: props.pricing.referral?.credits_reward ?? 0 },
    credits: {
        actions: { ...(props.pricing.credits?.actions || {}) },
        packs: (props.pricing.credits?.packs || []).map((p) => ({ ...p })),
    },
});

const save = () => form.put(route('admin.pricing.update'));
</script>
