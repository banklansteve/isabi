<template>
    <Head title="Billing issues" />

    <AdminChrome title="Billing issues" eyebrow="Customer token problems" />

    <section class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.05] sm:p-5">
        <AdminEmpty
            v-if="!issues.length"
            title="No open billing issues"
            description="Failed payments, multi-charges, and chargebacks assigned to you will show here."
            icon="ti ti-credit-card"
        />

        <ul v-else class="divide-y divide-ink/[0.06]">
            <li v-for="issue in issues" :key="issue.uid" class="flex flex-col gap-3 py-4 sm:flex-row sm:items-start">
                <div class="min-w-0 flex-1">
                    <p class="text-[13px] font-bold text-ink">{{ issue.title }}</p>
                    <p class="mt-1 text-[12px] font-medium text-ink/45">
                        {{ issue.user?.name }} · {{ issue.type }} · {{ issue.status }} · {{ issue.when }}
                    </p>
                    <p v-if="issue.details" class="mt-2 text-[13px] font-medium text-ink/70">{{ issue.details }}</p>
                    <p v-if="issue.amount_label" class="mt-1 text-[12px] font-bold text-ink">{{ issue.amount_label }}</p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <FormButton
                        v-if="issue.status === 'open'"
                        variant="secondary"
                        label="Claim"
                        @click="claim(issue)"
                    />
                    <FormButton variant="primary" label="Resolve" @click="resolve(issue, 'resolved')" />
                    <FormButton variant="secondary" label="Dismiss" @click="resolve(issue, 'dismissed')" />
                </div>
            </li>
        </ul>
    </section>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import { Head, router } from '@inertiajs/vue3';

defineProps({
    issues: { type: Array, default: () => [] },
    is_super: { type: Boolean, default: false },
});

const claim = (issue) => {
    router.post(route('admin.billing-issues.assign', issue.uid), {}, { preserveScroll: true });
};

const resolve = (issue, status) => {
    const note = window.prompt('Resolution note') || '';
    if (!note.trim()) return;
    router.post(route('admin.billing-issues.resolve', issue.uid), { status, note }, { preserveScroll: true });
};
</script>
