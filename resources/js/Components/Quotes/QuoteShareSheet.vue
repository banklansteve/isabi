<template>
    <AppModal
        :show="show"
        title="Send quote link"
        description="Share the quote link with your client on WhatsApp — same flow as review invites."
        icon="ti ti-brand-whatsapp"
        icon-tone="whatsapp"
        size="md"
        sheet
        @close="emit('close')"
    >
        <div class="space-y-3">
            <div
                v-if="share?.message"
                class="max-h-32 overflow-y-auto rounded-2xl bg-pale/80 px-3.5 py-3 text-[12px] font-medium leading-relaxed text-ink/60"
            >
                {{ share.message }}
            </div>

            <a
                v-if="launchHref"
                :href="launchHref"
                :target="isMobile ? '_self' : '_blank'"
                class="tap-target flex w-full items-center justify-center gap-2 rounded-2xl bg-[#25D366] px-5 py-3.5 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(37,211,102,0.5)] transition-opacity hover:opacity-95"
                @click="emit('close')"
            >
                <i class="ti ti-brand-whatsapp text-lg" aria-hidden="true" />
                Open WhatsApp
            </a>

            <button
                type="button"
                class="tap-target flex w-full items-center justify-center gap-2 rounded-2xl bg-pale px-5 py-3.5 text-sm font-bold text-ink ring-1 ring-ink/[0.06] transition hover:bg-tint/60 disabled:opacity-50"
                :disabled="!share?.pdf_url || copyingPdf"
                @click="copyPdfLink"
            >
                <i :class="pdfCopied ? 'ti ti-check text-emerald-600' : 'ti ti-file-type-pdf'" aria-hidden="true" />
                {{ pdfCopied ? 'PDF link copied' : 'Copy PDF link' }}
            </button>

            <button
                type="button"
                class="tap-target flex w-full items-center justify-center gap-2 rounded-2xl bg-pale px-5 py-3.5 text-sm font-bold text-ink ring-1 ring-ink/[0.06] transition hover:bg-tint/60 disabled:opacity-50"
                :disabled="!share?.quote_url || copying"
                @click="copyLink"
            >
                <i :class="linkCopied ? 'ti ti-check text-emerald-600' : 'ti ti-copy'" aria-hidden="true" />
                {{ linkCopied ? 'Link copied' : 'Copy quote link' }}
            </button>
        </div>
    </AppModal>
</template>

<script setup>
import AppModal from '@/Components/App/AppModal.vue';
import { copyToClipboard } from '@/utils/clipboard';
import { isMobileDevice, whatsappLaunchHref } from '@/utils/openWhatsApp';
import { computed, ref } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    share: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const isMobile = isMobileDevice();
const linkCopied = ref(false);
const pdfCopied = ref(false);
const copying = ref(false);
const copyingPdf = ref(false);

const launchHref = computed(() =>
    props.share?.whatsapp_app_url ? whatsappLaunchHref(props.share.whatsapp_app_url) : '',
);

const copyLink = async () => {
    if (!props.share?.quote_url) {
        return;
    }
    copying.value = true;
    linkCopied.value = await copyToClipboard(props.share.quote_url);
    copying.value = false;
};

const copyPdfLink = async () => {
    if (!props.share?.pdf_url) {
        return;
    }
    copyingPdf.value = true;
    pdfCopied.value = await copyToClipboard(props.share.pdf_url);
    copyingPdf.value = false;
};
</script>
