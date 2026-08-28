<template>
    <Head title="Approvals" />

    <AdminChrome title="Approvals" eyebrow="Operations requests" />

    <div class="space-y-6">
        <section class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5">
            <h2 class="text-sm font-bold text-ink">Pending</h2>
            <p class="mt-1 text-[13px] font-medium text-ink/45">
                Sensitive ops actions wait here until you approve or reject them.
            </p>

            <AdminEmpty
                v-if="!pending.length"
                class="mt-4"
                title="Nothing waiting"
                description="When operations request a suspension, hide, or message send, it lands here."
                icon="ti ti-shield-check"
            />

            <ul v-else class="mt-4 divide-y divide-ink/[0.06]">
                <li v-for="item in pending" :key="item.uid" class="flex flex-col gap-3 py-4 sm:flex-row sm:items-start">
                    <div class="min-w-0 flex-1">
                        <p class="text-[13px] font-bold text-ink">{{ item.action_label }}</p>
                        <p class="mt-1 text-[12px] font-medium text-ink/45">
                            {{ item.requester?.name }} · {{ item.when }}
                        </p>
                        <p v-if="item.reason" class="mt-2 text-[13px] font-medium text-ink/70">{{ item.reason }}</p>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <FormButton variant="secondary" label="Reject" @click="decide(item, 'reject')" />
                        <FormButton variant="primary" label="Approve" @click="decide(item, 'approve')" />
                    </div>
                </li>
            </ul>
        </section>

        <section v-if="recent.length" class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5">
            <h2 class="text-sm font-bold text-ink">Recent decisions</h2>
            <ul class="mt-3 divide-y divide-ink/[0.06]">
                <li v-for="item in recent" :key="item.uid" class="py-3">
                    <p class="text-[13px] font-bold text-ink">
                        {{ item.action_label }}
                        <span class="ms-2 text-[11px] font-bold uppercase tracking-wide text-ink/40">{{ item.status }}</span>
                    </p>
                    <p class="mt-1 text-[12px] font-medium text-ink/45">
                        {{ item.requester?.name }} · {{ item.reviewed_when || item.when }}
                    </p>
                </li>
            </ul>
        </section>
    </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import { Head, router } from '@inertiajs/vue3';

defineProps({
    pending: { type: Array, default: () => [] },
    recent: { type: Array, default: () => [] },
});

const decide = (item, action) => {
    const note = window.prompt(action === 'approve' ? 'Optional note' : 'Rejection note') ?? '';
    router.post(route(`admin.approvals.${action}`, item.uid), { note }, { preserveScroll: true });
};
</script>
