<template>
    <AppModal
        :show="show"
        :title="isReminder ? 'Send reminder' : 'Send review link'"
        :description="description"
        icon="ti ti-brand-whatsapp"
        icon-tone="whatsapp"
        size="md"
        sheet
        @close="emit('close')"
    >
        <div class="space-y-3">
            <div
                v-if="message"
                class="max-h-32 overflow-y-auto rounded-2xl bg-pale/80 px-3.5 py-3 text-[12px] font-medium leading-relaxed text-ink/60"
            >
                {{ message }}
            </div>

            <!--
              HTTPS only (wa.me / WhatsApp Web). Never whatsapp:// or intent:// —
              those force a second browser “Open WhatsApp?” alert after this modal.
            -->
            <a
                v-if="launchHref"
                :href="launchHref"
                :target="isMobile ? '_self' : '_blank'"
                :rel="isMobile ? undefined : 'noopener noreferrer'"
                class="tap-target flex w-full items-center justify-center gap-2 rounded-2xl bg-[#25D366] px-5 py-3.5 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(37,211,102,0.5)] transition-opacity hover:opacity-95"
                @click="onOpenWhatsApp"
            >
                <i class="ti ti-brand-whatsapp text-lg" aria-hidden="true" />
                Open WhatsApp
            </a>

            <button
                v-else
                type="button"
                class="tap-target flex w-full items-center justify-center gap-2 rounded-2xl bg-[#25D366] px-5 py-3.5 text-sm font-bold text-white opacity-60"
                disabled
            >
                <i class="ti ti-brand-whatsapp text-lg" aria-hidden="true" />
                Open WhatsApp
            </button>

            <p class="text-center text-[11px] font-medium leading-relaxed text-ink/40">
                {{ helperText }}
            </p>

            <button
                type="button"
                class="tap-target flex w-full items-center justify-center gap-2 rounded-2xl bg-pale px-5 py-3.5 text-sm font-bold text-ink ring-1 ring-ink/[0.06] transition hover:bg-tint/60 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="!resolvedReviewUrl || copying"
                @click="copyLink"
            >
                <i
                    :class="linkCopied ? 'ti ti-check text-emerald-600' : 'ti ti-copy'"
                    aria-hidden="true"
                />
                {{ linkCopied ? 'Link copied' : copying ? 'Copying…' : 'Copy review link' }}
            </button>

            <p
                v-if="resolvedReviewUrl"
                class="break-all rounded-xl bg-pale/80 px-3 py-2.5 text-[11px] font-medium leading-relaxed text-ink/45"
            >
                {{ resolvedReviewUrl }}
            </p>
        </div>
    </AppModal>
</template>

<script setup>
import AppModal from '@/Components/App/AppModal.vue';
import { copyToClipboard } from '@/utils/clipboard';
import { isMobileDevice, whatsappLaunchHref } from '@/utils/openWhatsApp';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    whatsappUrl: { type: String, default: '' },
    appUrl: { type: String, default: '' },
    protocolUrl: { type: String, default: '' },
    webUrl: { type: String, default: '' },
    reviewUrl: { type: String, default: '' },
    clientWhatsapp: { type: String, default: '' },
    message: { type: String, default: '' },
    kind: { type: String, default: 'invite' },
});

const emit = defineEmits(['close', 'opened']);

const linkCopied = ref(false);
const copying = ref(false);
let copyTimer = null;

const isMobile = computed(() => isMobileDevice());
const isReminder = computed(() => props.kind === 'reminder');

const resolvedReviewUrl = computed(() => {
    if (props.reviewUrl?.trim()) {
        return props.reviewUrl.trim();
    }
    // Fallback: pull the first URL out of the WhatsApp message body.
    const match = String(props.message || '').match(/https?:\/\/[^\s]+/i);
    return match?.[0]?.replace(/[).,;]+$/, '') || '';
});

const launchHref = computed(() =>
    whatsappLaunchHref({
        appUrl: props.appUrl || props.whatsappUrl || '',
        webUrl: props.webUrl || '',
        // Intentionally unused — protocol links cause the browser alert.
        protocolUrl: '',
    }),
);

const description = computed(() => {
    if (isReminder.value) {
        return props.clientWhatsapp?.trim()
            ? 'One gentle follow-up — WhatsApp opens with your reminder ready to send.'
            : 'One gentle follow-up — you’ll pick the chat, then send the ready reminder.';
    }

    return props.clientWhatsapp?.trim()
        ? 'Confirm below to open WhatsApp with this review message ready to send.'
        : 'Confirm below to open WhatsApp — you’ll pick the chat, then send the ready message.';
});

const helperText = computed(() =>
    isMobile.value
        ? 'Opens WhatsApp or WhatsApp Business on your phone.'
        : 'Continues to WhatsApp with your message ready — no extra browser prompts.',
);

const toast = (message, type = 'success') => {
    window.dispatchEvent(
        new CustomEvent('kraftrack:toast', {
            detail: { type, message, duration: 4000 },
        }),
    );
};

const onOpenWhatsApp = () => {
    emit('opened');
    window.setTimeout(() => emit('close'), 150);
};

const copyLink = async () => {
    const url = resolvedReviewUrl.value;
    if (!url || copying.value) {
        if (!url) {
            toast('Review link isn’t ready yet. Try again in a moment.', 'error');
        }
        return;
    }

    copying.value = true;
    try {
        const ok = await copyToClipboard(url);
        if (!ok) {
            toast('Couldn’t copy automatically — select the link below.', 'error');
            return;
        }
        linkCopied.value = true;
        toast('Review link copied.');
        window.clearTimeout(copyTimer);
        copyTimer = window.setTimeout(() => {
            linkCopied.value = false;
        }, 2200);
    } finally {
        copying.value = false;
    }
};

watch(
    () => props.show,
    (open) => {
        if (!open) {
            linkCopied.value = false;
            copying.value = false;
        }
    },
);
</script>
