<template>
    <div v-if="show" class="space-y-3">
        <div
            v-if="can.resolve && record?.pending_approval"
            class="rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3"
        >
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-violet-700">Pending your approval</p>
            <p class="mt-1 text-[13px] font-medium text-violet-950">
                Ops recommended: {{ record.recommended_outcome_label || record.recommended_outcome }}
            </p>
            <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                <FormButton
                    variant="primary"
                    class="w-full"
                    :label="approveLabel"
                    :loading="busy === 'approve'"
                    @click="$emit('approve')"
                />
                <FormButton
                    variant="secondary"
                    class="w-full"
                    label="Reject recommendation"
                    :loading="busy === 'reject'"
                    @click="$emit('reject')"
                />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <FormButton
                v-if="can.investigate && record.status === 'new'"
                variant="primary"
                class="w-full"
                label="Mark in review"
                :loading="busy === 'review'"
                @click="$emit('review')"
            />
            <FormButton
                v-if="can.investigate && !can.resolve"
                variant="secondary"
                class="w-full"
                label="Recommend dismiss"
                @click="$emit('recommend', 'dismiss')"
            />
            <FormButton
                v-if="can.investigate && !can.resolve && isReview"
                variant="secondary"
                class="w-full"
                label="Recommend hide"
                @click="$emit('recommend', 'hide')"
            />
            <FormButton
                v-if="can.investigate && !can.resolve"
                variant="secondary"
                class="w-full"
                :label="isReview ? 'Recommend remove' : 'Recommend removal'"
                @click="$emit('recommend', 'remove')"
            />
            <FormButton
                v-if="can.investigate && !can.resolve && record.user_url"
                variant="secondary"
                class="w-full"
                label="Recommend suspension"
                @click="$emit('recommend', 'suspend')"
            />
            <FormButton
                v-if="can.dismiss_low && !can.resolve"
                variant="secondary"
                class="w-full"
                label="Dismiss (low severity)"
                @click="$emit('dismiss')"
            />
            <FormButton
                v-if="can.resolve"
                variant="secondary"
                class="w-full"
                :label="record.severity === 'high' ? 'Dismiss high-severity case' : 'Dismiss'"
                @click="$emit('dismiss')"
            />
            <FormButton
                v-if="can.resolve && isReview"
                variant="secondary"
                class="w-full"
                label="Hide review"
                @click="$emit('hide')"
            />
            <FormButton
                v-if="can.resolve"
                variant="primary"
                class="w-full"
                :label="isReview ? 'Remove review' : 'Remove entry'"
                @click="$emit('remove')"
            />
            <FormButton
                v-if="can.resolve && record.user_url"
                variant="secondary"
                class="w-full"
                label="Warn or suspend on Users"
                @click="$emit('handoff')"
            />
            <FormButton
                v-if="can.refer"
                variant="secondary"
                class="w-full"
                label="Refer to colleague"
                @click="$emit('refer')"
            />
        </div>
    </div>
</template>

<script setup>
import FormButton from '@/Components/Form/FormButton.vue';
import { computed } from 'vue';

const props = defineProps({
    record: { type: Object, default: null },
    can: { type: Object, default: () => ({}) },
    busy: { type: String, default: '' },
    approveLabel: { type: String, default: 'Approve recommendation' },
});

defineEmits(['review', 'recommend', 'dismiss', 'hide', 'remove', 'handoff', 'approve', 'reject', 'refer']);

const isReview = computed(() => props.record?.kind === 'review');
const isOpen = computed(() => ['new', 'in_review', 'pending_approval'].includes(props.record?.status));
const show = computed(() => Boolean(props.record && isOpen.value && (props.can.investigate || props.can.resolve || props.can.refer)));
</script>
