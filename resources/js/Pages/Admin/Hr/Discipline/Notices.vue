<template>
    <Head title="Employment notices" />

    <AdminChrome title="Employment notices" eyebrow="Notices issued to you" />

    <p class="mb-5 max-w-2xl text-sm font-medium leading-relaxed text-ink/55">
        You can review the notice that was issued, acknowledge receipt, and submit one written response. This is not the investigation file.
    </p>

    <div class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
        <AdminEmpty
            v-if="notices.length === 0"
            title="No notices"
            description="When a formal notice is issued to you, it will appear here."
            icon="ti ti-mail"
        />
        <ul v-else class="divide-y divide-ink/10">
            <li v-for="notice in notices" :key="notice.id">
                <Link :href="route('admin.notices.show', notice.id)" class="flex items-start justify-between gap-3 px-5 py-4 hover:bg-pale/70 sm:px-6">
                    <div>
                        <p class="text-sm font-semibold text-ink">{{ notice.letter_subject }}</p>
                        <p class="mt-0.5 text-xs font-medium text-ink/45">{{ notice.reference }} · Issued {{ notice.issued_label }}</p>
                    </div>
                    <span class="text-xs font-semibold" :class="notice.acknowledged ? 'text-ink/40' : 'text-base-action'">
                        {{ notice.acknowledged ? 'Acknowledged' : 'Awaiting acknowledgment' }}
                    </span>
                </Link>
            </li>
        </ul>
    </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    notices: { type: Array, default: () => [] },
});
</script>
