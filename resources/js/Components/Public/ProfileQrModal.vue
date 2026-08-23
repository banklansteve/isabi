<template>
    <AppModal
        :show="show"
        title="Your page QR code"
        description="Print it on banners, vans, invoices or cards. One scan opens this page."
        icon="ti ti-qrcode"
        icon-tone="base"
        size="md"
        sheet
        @close="emit('close')"
    >
        <div class="space-y-4">
            <!-- Style -->
            <div
                class="grid grid-cols-2 gap-1 rounded-2xl bg-pale p-1 ring-1 ring-ink/[0.06]"
                role="radiogroup"
                aria-label="QR code style"
            >
                <button
                    v-for="option in styles"
                    :key="option.value"
                    type="button"
                    role="radio"
                    :aria-checked="style === option.value"
                    class="tap-target rounded-xl px-3 py-2.5 text-xs font-bold transition-colors"
                    :class="
                        style === option.value
                            ? 'bg-white text-ink shadow-premium'
                            : 'text-ink/45 hover:text-ink'
                    "
                    @click="style = option.value"
                >
                    {{ option.label }}
                </button>
            </div>

            <div
                class="relative mx-auto flex w-full items-center justify-center overflow-hidden rounded-2xl bg-pale ring-1 ring-ink/[0.06]"
                :class="style === 'poster' ? 'max-w-[280px]' : 'max-w-[240px]'"
            >
                <img
                    v-if="previewUrl"
                    :src="previewUrl"
                    alt="QR code preview"
                    class="block w-full"
                />
                <div v-else class="flex aspect-square w-full items-center justify-center">
                    <i class="ti ti-loader-2 animate-spin text-2xl text-ink/30" aria-hidden="true" />
                </div>
            </div>

            <p class="text-center text-[11px] font-medium leading-relaxed text-ink/40">
                {{
                    style === 'poster'
                        ? 'Ready to print as-is — your name, trade and link are included.'
                        : 'Just the code, on white. Drop it into your own artwork.'
                }}
            </p>

            <!-- Output size -->
            <div>
                <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.12em] text-ink/40">
                    Download size
                </p>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        v-for="option in sizes"
                        :key="option.value"
                        type="button"
                        class="tap-target rounded-xl px-2 py-2.5 text-center transition-colors"
                        :class="
                            size === option.value
                                ? 'bg-deep text-white'
                                : 'bg-pale text-ink/55 ring-1 ring-ink/[0.06] hover:bg-tint hover:text-deep'
                        "
                        @click="size = option.value"
                    >
                        <span class="block text-xs font-bold">{{ option.label }}</span>
                        <span class="mt-0.5 block text-[10px] font-semibold opacity-60">
                            {{ option.hint }}
                        </span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                <button
                    type="button"
                    class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-base-action px-4 py-3.5 text-sm font-bold text-white transition-colors hover:bg-base-hover disabled:opacity-50"
                    :disabled="busy || !url"
                    @click="downloadPng"
                >
                    <i
                        :class="busy ? 'ti ti-loader-2 animate-spin' : 'ti ti-download'"
                        class="text-base"
                        aria-hidden="true"
                    />
                    Download PNG
                </button>
                <button
                    type="button"
                    class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-4 py-3.5 text-sm font-bold text-ink ring-1 ring-ink/[0.08] transition-colors hover:bg-pale disabled:opacity-50"
                    :disabled="busy || !url"
                    @click="downloadSvg"
                >
                    <i class="ti ti-vector text-base" aria-hidden="true" />
                    Download SVG
                </button>
            </div>

            <p class="text-center text-[11px] font-medium leading-relaxed text-ink/40">
                SVG stays sharp at any size — use it for large banners and vehicle wraps.
            </p>

            <div class="grid gap-2.5" :class="canShareFiles ? 'sm:grid-cols-2' : ''">
                <button
                    v-if="canShareFiles"
                    type="button"
                    class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-pale px-4 py-3.5 text-sm font-bold text-ink ring-1 ring-ink/[0.07] transition-colors hover:bg-tint/60"
                    @click="shareImage"
                >
                    <i class="ti ti-share-2 text-base" aria-hidden="true" />
                    Send to printer
                </button>
                <button
                    type="button"
                    class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-pale px-4 py-3.5 text-sm font-bold text-ink ring-1 ring-ink/[0.07] transition-colors hover:bg-tint/60"
                    @click="copyUrl"
                >
                    <i
                        :class="copied ? 'ti ti-check text-emerald-600' : 'ti ti-copy'"
                        class="text-base"
                        aria-hidden="true"
                    />
                    {{ copied ? 'Link copied' : 'Copy page link' }}
                </button>
            </div>

            <p
                v-if="url"
                class="break-all rounded-xl bg-pale/80 px-3.5 py-2.5 text-center text-[11px] font-semibold text-ink/45"
            >
                {{ url }}
            </p>
        </div>
    </AppModal>
</template>

<script setup>
import AppModal from '@/Components/App/AppModal.vue';
import QRCode from 'qrcode';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    url: { type: String, default: '' },
    filename: { type: String, default: 'isabi-page-qr' },
    businessName: { type: String, default: '' },
    trade: { type: String, default: '' },
});

const emit = defineEmits(['close']);

const styles = [
    { value: 'poster', label: 'Print-ready poster' },
    { value: 'plain', label: 'Plain code' },
];

const sizes = [
    { value: 1024, label: 'Web', hint: '1024px' },
    { value: 2048, label: 'Print', hint: '2048px' },
    { value: 4096, label: 'Banner', hint: '4096px' },
];

const style = ref('poster');
const size = ref(2048);
const previewUrl = ref('');
const copied = ref(false);
const busy = ref(false);
const canShareFiles = ref(false);
let copyTimer = null;

const POSTER_RATIO = 1350 / 1080;

const toast = (message, type = 'success') => {
    window.dispatchEvent(
        new CustomEvent('isabi:toast', { detail: { type, message, duration: 3500 } }),
    );
};

const outputName = computed(
    () => `${props.filename || 'isabi-page-qr'}-${style.value}`,
);

/**
 * Level H keeps ~30% of the code recoverable, which matters once it's printed
 * on vinyl, folded on a flyer, or photographed at an angle.
 */
const qrOptions = (width, dark = '#0B1F3A') => ({
    errorCorrectionLevel: 'H',
    margin: 1,
    width,
    color: { dark, light: '#FFFFFF' },
});

const loadImage = (src) =>
    new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = src;
    });

const roundedRect = (ctx, x, y, w, h, r) => {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
};

const fitText = (ctx, text, maxWidth, startPx, weight, family) => {
    let px = startPx;
    ctx.font = `${weight} ${px}px ${family}`;
    while (px > 24 && ctx.measureText(text).width > maxWidth) {
        px -= 4;
        ctx.font = `${weight} ${px}px ${family}`;
    }
    return px;
};

const buildPlain = async (width) => {
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = width;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(0, 0, width, width);

    const qr = await loadImage(await QRCode.toDataURL(props.url, qrOptions(width)));
    ctx.drawImage(qr, 0, 0, width, width);

    return canvas;
};

const buildPoster = async (width) => {
    const height = Math.round(width * POSTER_RATIO);
    const s = width / 1080;

    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');

    ctx.fillStyle = '#071427';
    ctx.fillRect(0, 0, width, height);

    const glow = ctx.createRadialGradient(
        width * 0.15, 0, 0,
        width * 0.15, 0, width * 0.9,
    );
    glow.addColorStop(0, 'rgba(47,111,237,0.35)');
    glow.addColorStop(1, 'rgba(47,111,237,0)');
    ctx.fillStyle = glow;
    ctx.fillRect(0, 0, width, height);

    const warm = ctx.createRadialGradient(
        width, height, 0,
        width, height, width * 0.8,
    );
    warm.addColorStop(0, 'rgba(255,106,61,0.22)');
    warm.addColorStop(1, 'rgba(255,106,61,0)');
    ctx.fillStyle = warm;
    ctx.fillRect(0, 0, width, height);

    const pad = 84 * s;
    const mid = width / 2;
    ctx.textBaseline = 'top';
    ctx.textAlign = 'center';

    ctx.font = `800 ${26 * s}px "Plus Jakarta Sans", system-ui, sans-serif`;
    ctx.fillStyle = '#FF6A3D';
    ctx.letterSpacing = `${4 * s}px`;
    ctx.fillText('SCAN TO SEE MY WORK', mid, 110 * s);
    ctx.letterSpacing = '0px';

    const name = props.businessName || 'My Isabi page';
    const namePx = fitText(ctx, name, width - pad * 2, 76 * s, 600, 'Fraunces, Georgia, serif');
    ctx.fillStyle = '#FFFFFF';
    ctx.font = `600 ${namePx}px Fraunces, Georgia, serif`;
    ctx.fillText(name, mid, 162 * s);

    if (props.trade) {
        ctx.font = `600 ${30 * s}px "Plus Jakarta Sans", system-ui, sans-serif`;
        ctx.fillStyle = 'rgba(255,255,255,0.5)';
        ctx.fillText(props.trade, mid, 162 * s + namePx + 16 * s);
    }

    // QR sits on a white card so it scans reliably against the dark artwork.
    const cardSize = 760 * s;
    const cardX = mid - cardSize / 2;
    const cardY = 330 * s;
    ctx.fillStyle = '#FFFFFF';
    roundedRect(ctx, cardX, cardY, cardSize, cardSize, 48 * s);
    ctx.fill();

    const inset = 44 * s;
    const qrSize = Math.round(cardSize - inset * 2);
    const qr = await loadImage(await QRCode.toDataURL(props.url, qrOptions(qrSize)));
    ctx.drawImage(qr, cardX + inset, cardY + inset, qrSize, qrSize);

    const link = (props.url || '').replace(/^https?:\/\//, '');
    const linkPx = fitText(
        ctx,
        link,
        width - pad * 2,
        36 * s,
        700,
        '"Plus Jakarta Sans", system-ui, sans-serif',
    );
    const linkY = cardY + cardSize + 56 * s;
    ctx.font = `700 ${linkPx}px "Plus Jakarta Sans", system-ui, sans-serif`;
    ctx.fillStyle = '#FFFFFF';
    ctx.fillText(link, mid, linkY);

    ctx.font = `500 ${26 * s}px "Plus Jakarta Sans", system-ui, sans-serif`;
    ctx.fillStyle = 'rgba(255,255,255,0.45)';
    ctx.fillText('Photos of real jobs, reviewed by real clients.', mid, linkY + linkPx + 18 * s);

    ctx.font = `800 ${26 * s}px "Plus Jakarta Sans", system-ui, sans-serif`;
    ctx.fillStyle = 'rgba(255,255,255,0.35)';
    ctx.fillText('isabi', mid, height - 96 * s);
    ctx.textAlign = 'left';

    return canvas;
};

const buildCanvas = (width) =>
    style.value === 'poster' ? buildPoster(width) : buildPlain(width);

const canvasToBlob = (canvas) =>
    new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));

const renderPreview = async () => {
    previewUrl.value = '';
    if (!props.show || !props.url) {
        return;
    }

    try {
        if (document.fonts?.ready) {
            await document.fonts.ready;
        }
        const canvas = await buildCanvas(720);
        previewUrl.value = canvas.toDataURL('image/png');

        const blob = await canvasToBlob(canvas);
        canShareFiles.value =
            !!navigator.canShare &&
            !!blob &&
            navigator.canShare({
                files: [new File([blob], 'qr.png', { type: 'image/png' })],
            });
    } catch {
        toast('Couldn’t generate the QR code. Try again.', 'error');
    }
};

const downloadPng = async () => {
    if (!props.url || busy.value) {
        return;
    }
    busy.value = true;
    try {
        const canvas = await buildCanvas(size.value);
        const link = document.createElement('a');
        link.download = `${outputName.value}-${size.value}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
        toast('QR code downloaded.');
    } catch {
        toast('Couldn’t build that file. Try a smaller size.', 'error');
    } finally {
        busy.value = false;
    }
};

const downloadSvg = async () => {
    if (!props.url || busy.value) {
        return;
    }
    busy.value = true;
    try {
        // Vector output is the plain code — the poster artwork is raster.
        const svg = await QRCode.toString(props.url, {
            type: 'svg',
            errorCorrectionLevel: 'H',
            margin: 1,
            color: { dark: '#0B1F3A', light: '#FFFFFF' },
        });
        const blob = new Blob([svg], { type: 'image/svg+xml' });
        const href = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.download = `${props.filename || 'isabi-page-qr'}.svg`;
        link.href = href;
        link.click();
        URL.revokeObjectURL(href);
        toast('Vector QR downloaded.');
    } catch {
        toast('Couldn’t build the SVG. Try the PNG instead.', 'error');
    } finally {
        busy.value = false;
    }
};

const shareImage = async () => {
    if (!props.url || busy.value) {
        return;
    }
    busy.value = true;
    try {
        const canvas = await buildCanvas(size.value);
        const blob = await canvasToBlob(canvas);
        if (!blob) {
            return;
        }
        await navigator.share({
            files: [
                new File([blob], `${outputName.value}.png`, { type: 'image/png' }),
            ],
            title: props.businessName || 'Isabi page QR',
        });
    } catch {
        // Cancelled or unsupported — download remains available.
    } finally {
        busy.value = false;
    }
};

const copyUrl = async () => {
    if (!props.url) {
        return;
    }
    try {
        await navigator.clipboard.writeText(props.url);
        copied.value = true;
        window.clearTimeout(copyTimer);
        copyTimer = window.setTimeout(() => {
            copied.value = false;
        }, 2200);
        toast('Page link copied.');
    } catch {
        toast('Couldn’t copy the link automatically.', 'error');
    }
};

watch(
    () => [props.show, props.url, style.value],
    () => {
        if (!props.show) {
            copied.value = false;
            return;
        }
        renderPreview();
    },
    { immediate: true },
);

onBeforeUnmount(() => window.clearTimeout(copyTimer));
</script>
