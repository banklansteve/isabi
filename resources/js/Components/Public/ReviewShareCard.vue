<template>
    <AppModal
        :show="show"
        title="Share this job"
        :description="hasReview
            ? 'Send the job and its review as a link or a ready-made image.'
            : 'Send this job as a link or a ready-made image.'"
        icon="ti ti-share-2"
        size="md"
        sheet
        @close="$emit('close')"
    >
        <div class="space-y-4">
            <div class="overflow-hidden rounded-xl bg-pale ring-1 ring-ink/[0.06] sm:rounded-2xl">
                <img
                    v-if="previewUrl"
                    :src="previewUrl"
                    alt="Preview of the shareable card"
                    class="block w-full"
                />
                <div v-else class="flex aspect-[4/5] items-center justify-center">
                    <i class="ti ti-loader-2 animate-spin text-2xl text-ink/30" aria-hidden="true" />
                </div>
            </div>

            <button
                v-if="canShareNative"
                type="button"
                class="tap-target flex w-full items-center justify-center gap-2 rounded-xl bg-base-action px-5 py-3.5 text-sm font-bold text-white transition-colors hover:bg-base-hover disabled:opacity-50 sm:rounded-2xl"
                :disabled="busy || (!blob && !jobUrl)"
                @click="shareNative"
            >
                <i
                    :class="busy ? 'ti ti-loader-2 animate-spin' : 'ti ti-share-2'"
                    class="text-lg"
                    aria-hidden="true"
                />
                {{ canShareFiles ? 'Share image' : 'Share' }}
            </button>

            <div class="grid grid-cols-4 gap-2">
                <a
                    :href="whatsappHref"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="tap-target flex flex-col items-center justify-center gap-1.5 rounded-xl bg-pale py-3 text-[11px] font-bold text-ink/60 ring-1 ring-ink/[0.06] transition-colors hover:bg-[#25D366]/10 hover:text-[#128C4B] sm:rounded-2xl"
                >
                    <i class="ti ti-brand-whatsapp text-xl" aria-hidden="true" />
                    WhatsApp
                </a>
                <a
                    :href="xHref"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="tap-target flex flex-col items-center justify-center gap-1.5 rounded-xl bg-pale py-3 text-[11px] font-bold text-ink/60 ring-1 ring-ink/[0.06] transition-colors hover:bg-ink/5 hover:text-ink sm:rounded-2xl"
                >
                    <i class="ti ti-brand-x text-xl" aria-hidden="true" />
                    X
                </a>
                <a
                    :href="facebookHref"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="tap-target flex flex-col items-center justify-center gap-1.5 rounded-xl bg-pale py-3 text-[11px] font-bold text-ink/60 ring-1 ring-ink/[0.06] transition-colors hover:bg-[#1877F2]/10 hover:text-[#1877F2] sm:rounded-2xl"
                >
                    <i class="ti ti-brand-facebook text-xl" aria-hidden="true" />
                    Facebook
                </a>
                <button
                    type="button"
                    class="tap-target flex flex-col items-center justify-center gap-1.5 rounded-xl bg-pale py-3 text-[11px] font-bold text-ink/60 ring-1 ring-ink/[0.06] transition-colors hover:bg-tint hover:text-deep sm:rounded-2xl"
                    @click="copyLink"
                >
                    <i
                        :class="copied ? 'ti ti-check text-emerald-600' : 'ti ti-link'"
                        class="text-xl"
                        aria-hidden="true"
                    />
                    {{ copied ? 'Copied' : 'Copy link' }}
                </button>
            </div>

            <div class="grid gap-2.5" :class="canCopyImage ? 'grid-cols-2' : ''">
                <button
                    v-if="canCopyImage"
                    type="button"
                    class="tap-target flex items-center justify-center gap-2 rounded-xl bg-pale px-4 py-3.5 text-sm font-bold text-ink ring-1 ring-ink/[0.07] transition-colors hover:bg-tint/60 disabled:opacity-50 sm:rounded-2xl"
                    :disabled="!blob || busy"
                    @click="copyImage"
                >
                    <i
                        :class="imageCopied ? 'ti ti-check text-emerald-600' : 'ti ti-photo'"
                        class="text-lg"
                        aria-hidden="true"
                    />
                    {{ imageCopied ? 'Image copied' : 'Copy image' }}
                </button>
                <button
                    type="button"
                    class="tap-target flex items-center justify-center gap-2 rounded-xl bg-pale px-4 py-3.5 text-sm font-bold text-ink ring-1 ring-ink/[0.07] transition-colors hover:bg-tint/60 disabled:opacity-50 sm:rounded-2xl"
                    :disabled="!previewUrl || busy"
                    @click="download"
                >
                    <i class="ti ti-download text-lg" aria-hidden="true" />
                    Download
                </button>
            </div>

            <p
                v-if="jobUrl"
                class="break-all rounded-xl bg-pale/80 px-3 py-2.5 text-center text-[11px] font-medium text-ink/40"
            >
                {{ jobUrl }}
            </p>

            <p v-if="hasReview" class="text-center text-[11px] font-medium leading-relaxed text-ink/40">
                The review text is reproduced exactly as the client wrote it.
            </p>
        </div>
    </AppModal>
</template>

<script setup>
import AppModal from '@/Components/App/AppModal.vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    job: { type: Object, default: null },
    businessName: { type: String, default: '' },
    trade: { type: String, default: '' },
    pageUrl: { type: String, default: '' },
});

defineEmits(['close']);

const WIDTH = 1080;
const HEIGHT = 1350;

const previewUrl = ref('');
const blob = ref(null);
const canShareFiles = ref(false);
const canShareNative = ref(false);
const canCopyImage = ref(false);
const copied = ref(false);
const imageCopied = ref(false);
const busy = ref(false);
let copyTimer = null;
let imageCopyTimer = null;

const hasReview = computed(() => !!props.job?.review);

/** Deep link straight to this card on the public page. */
const jobUrl = computed(() => {
    if (props.job?.public_url) {
        return props.job.public_url;
    }
    if (props.job?.uid && props.pageUrl) {
        return `${props.pageUrl}#job-${props.job.uid}`;
    }
    return props.pageUrl;
});

const shareText = computed(() => {
    const who = props.businessName || 'this artisan';
    const title = props.job?.description;

    if (hasReview.value) {
        const rating = Number(props.job.review.rating).toFixed(1);
        return `${rating}★ for ${who}${title ? ` — “${title}”` : ''}. See the photos and the client's own words:`;
    }

    return `${who}${title ? ` — “${title}”` : ''}. See the photos:`;
});

const whatsappHref = computed(
    () => `https://wa.me/?text=${encodeURIComponent(`${shareText.value}\n${jobUrl.value}`)}`,
);

const xHref = computed(
    () =>
        `https://twitter.com/intent/tweet?text=${encodeURIComponent(shareText.value)}&url=${encodeURIComponent(jobUrl.value)}`,
);

const facebookHref = computed(
    () => `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(jobUrl.value)}`,
);

const toast = (message, type = 'success') => {
    window.dispatchEvent(
        new CustomEvent('isabi:toast', { detail: { type, message, duration: 3500 } }),
    );
};

const fileName = () => `isabi-${(props.job?.uid || 'job').slice(0, 8)}.png`;

const roundedRect = (ctx, x, y, w, h, r) => {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
};

const wrapText = (ctx, text, maxWidth, maxLines) => {
    const words = String(text).split(/\s+/).filter(Boolean);
    const lines = [];
    let line = '';
    let truncated = false;

    for (const word of words) {
        const candidate = line ? `${line} ${word}` : word;
        if (ctx.measureText(candidate).width <= maxWidth) {
            line = candidate;
            continue;
        }
        if (line) {
            lines.push(line);
        }
        if (lines.length === maxLines) {
            truncated = true;
            line = '';
            break;
        }
        line = word;
    }

    if (line && lines.length < maxLines) {
        lines.push(line);
    } else if (line) {
        truncated = true;
    }

    if (truncated && lines.length) {
        let last = lines[lines.length - 1];
        while (last && ctx.measureText(`${last}…`).width > maxWidth) {
            last = last.slice(0, -1);
        }
        lines[lines.length - 1] = `${last}…`;
    }

    return lines;
};

const drawStar = (ctx, cx, cy, radius, fillRatio) => {
    const spikes = 5;
    const inner = radius * 0.45;

    const path = () => {
        ctx.beginPath();
        for (let i = 0; i < spikes * 2; i += 1) {
            const r = i % 2 === 0 ? radius : inner;
            const angle = (Math.PI / spikes) * i - Math.PI / 2;
            const x = cx + Math.cos(angle) * r;
            const y = cy + Math.sin(angle) * r;
            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        }
        ctx.closePath();
    };

    ctx.save();
    path();
    ctx.strokeStyle = 'rgba(255,255,255,0.28)';
    ctx.lineWidth = 3;
    ctx.stroke();
    ctx.restore();

    if (fillRatio <= 0) {
        return;
    }

    ctx.save();
    ctx.beginPath();
    ctx.rect(cx - radius, cy - radius, radius * 2 * fillRatio, radius * 2);
    ctx.clip();
    path();
    ctx.fillStyle = '#F5A524';
    ctx.fill();
    ctx.restore();
};

const loadImage = (src) =>
    new Promise((resolve) => {
        if (!src) {
            resolve(null);
            return;
        }
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => resolve(img);
        img.onerror = () => resolve(null);
        img.src = src;
    });

const drawCover = (ctx, img, dx, dy, dw, dh) => {
    const scale = Math.max(dw / img.width, dh / img.height);
    const sw = dw / scale;
    const sh = dh / scale;
    ctx.drawImage(img, (img.width - sw) / 2, (img.height - sh) / 2, sw, sh, dx, dy, dw, dh);
};

const render = async () => {
    const job = props.job;
    if (!job) {
        return;
    }

    const canvas = document.createElement('canvas');
    canvas.width = WIDTH;
    canvas.height = HEIGHT;
    const ctx = canvas.getContext('2d');

    ctx.fillStyle = '#071427';
    ctx.fillRect(0, 0, WIDTH, HEIGHT);

    const photo = await loadImage(job.media?.[0]?.thumb_url || job.media?.[0]?.url);
    const bandHeight = 520;
    if (photo) {
        drawCover(ctx, photo, 0, 0, WIDTH, bandHeight);
        const fade = ctx.createLinearGradient(0, 0, 0, bandHeight);
        fade.addColorStop(0, 'rgba(7,20,39,0.35)');
        fade.addColorStop(1, 'rgba(7,20,39,1)');
        ctx.fillStyle = fade;
        ctx.fillRect(0, 0, WIDTH, bandHeight);
    }

    const pad = 84;
    let y = photo ? bandHeight - 40 : 160;

    ctx.fillStyle = '#FFFFFF';
    ctx.font = '600 62px Fraunces, Georgia, serif';
    ctx.textBaseline = 'top';
    wrapText(ctx, job.description || 'Completed job', WIDTH - pad * 2, 3).forEach((line) => {
        ctx.fillText(line, pad, y);
        y += 74;
    });

    y += 8;
    ctx.font = '600 28px "Plus Jakarta Sans", system-ui, sans-serif';
    ctx.fillStyle = 'rgba(255,255,255,0.5)';
    const meta = [job.category_label || job.job_category, job.worked_on_label]
        .filter(Boolean)
        .join('  ·  ');
    if (meta) {
        ctx.fillText(meta, pad, y);
        y += 56;
    }

    if (job.review) {
        y += 24;
        const panelX = pad;
        const panelW = WIDTH - pad * 2;
        const panelY = y;

        ctx.font = '400 36px Fraunces, Georgia, serif';
        const quoteLines = wrapText(
            ctx,
            `“${job.review.comment || 'Rated this job.'}”`,
            panelW - 72,
            7,
        );
        const panelH = 96 + quoteLines.length * 52 + 86;

        ctx.fillStyle = 'rgba(255,255,255,0.06)';
        roundedRect(ctx, panelX, panelY, panelW, panelH, 36);
        ctx.fill();

        const rating = Number(job.review.rating) || 0;
        let starX = panelX + 58;
        const starY = panelY + 48;
        for (let i = 1; i <= 5; i += 1) {
            drawStar(ctx, starX, starY, 22, Math.max(0, Math.min(1, rating - (i - 1))));
            starX += 54;
        }

        ctx.font = '800 30px "Plus Jakarta Sans", system-ui, sans-serif';
        ctx.fillStyle = '#F5A524';
        ctx.textBaseline = 'middle';
        ctx.fillText(rating.toFixed(1), starX + 6, starY + 1);
        ctx.textBaseline = 'top';

        let quoteY = panelY + 96;
        ctx.font = '400 36px Fraunces, Georgia, serif';
        ctx.fillStyle = 'rgba(255,255,255,0.88)';
        quoteLines.forEach((line) => {
            ctx.fillText(line, panelX + 36, quoteY);
            quoteY += 52;
        });

        quoteY += 18;
        ctx.font = '700 27px "Plus Jakarta Sans", system-ui, sans-serif';
        ctx.fillStyle = 'rgba(255,255,255,0.45)';
        ctx.fillText(
            [job.review.client_display_name || 'Verified client', job.review.submitted_at_label]
                .filter(Boolean)
                .join('  ·  '),
            panelX + 36,
            quoteY,
        );
    }

    const footerY = HEIGHT - 150;
    ctx.font = '700 40px "Plus Jakarta Sans", system-ui, sans-serif';
    ctx.fillStyle = '#FFFFFF';
    ctx.fillText(props.businessName || 'Isabi', pad, footerY);

    ctx.font = '500 27px "Plus Jakarta Sans", system-ui, sans-serif';
    ctx.fillStyle = 'rgba(255,255,255,0.45)';
    ctx.fillText(
        [props.trade, props.pageUrl?.replace(/^https?:\/\//, '')].filter(Boolean).join('  ·  '),
        pad,
        footerY + 52,
    );

    ctx.font = '800 26px "Plus Jakarta Sans", system-ui, sans-serif';
    ctx.fillStyle = 'rgba(255,255,255,0.3)';
    ctx.textAlign = 'right';
    ctx.fillText('isabi', WIDTH - pad, footerY + 14);
    ctx.textAlign = 'left';

    await new Promise((resolve) => {
        canvas.toBlob((result) => {
            if (result) {
                if (previewUrl.value) {
                    URL.revokeObjectURL(previewUrl.value);
                }
                blob.value = result;
                previewUrl.value = URL.createObjectURL(result);
            }
            resolve();
        }, 'image/png');
    });
};

const download = () => {
    if (!previewUrl.value) {
        return;
    }
    const link = document.createElement('a');
    link.href = previewUrl.value;
    link.download = fileName();
    link.click();
    toast('Image downloaded.');
};

const shareNative = async () => {
    if (busy.value || typeof navigator.share !== 'function') {
        return;
    }

    busy.value = true;
    try {
        const file = blob.value
            ? new File([blob.value], fileName(), { type: 'image/png' })
            : null;

        if (file && navigator.canShare?.({ files: [file] })) {
            await navigator.share({
                files: [file],
                title: props.businessName || 'Isabi',
                text: shareText.value,
            });
            return;
        }

        await navigator.share({
            title: props.businessName || 'Isabi',
            text: `${shareText.value}\n${jobUrl.value}`,
            url: jobUrl.value,
        });
    } catch (error) {
        if (error?.name !== 'AbortError') {
            toast('Couldn’t open the share sheet. Use WhatsApp or copy the link.', 'error');
        }
    } finally {
        busy.value = false;
    }
};

const copyLink = async () => {
    try {
        await navigator.clipboard.writeText(jobUrl.value);
        copied.value = true;
        window.clearTimeout(copyTimer);
        copyTimer = window.setTimeout(() => {
            copied.value = false;
        }, 2200);
        toast('Link copied.');
    } catch {
        toast('Couldn’t copy the link automatically.', 'error');
    }
};

const copyImage = async () => {
    if (!blob.value || busy.value) {
        return;
    }
    busy.value = true;
    try {
        await navigator.clipboard.write([
            new ClipboardItem({ 'image/png': blob.value }),
        ]);
        imageCopied.value = true;
        window.clearTimeout(imageCopyTimer);
        imageCopyTimer = window.setTimeout(() => {
            imageCopied.value = false;
        }, 2200);
        toast('Image copied — paste it into WhatsApp, Instagram or email.');
    } catch {
        toast('Couldn’t copy the image. Download it instead.', 'error');
    } finally {
        busy.value = false;
    }
};

watch(
    () => props.show,
    async (open) => {
        if (!open) {
            copied.value = false;
            imageCopied.value = false;
            return;
        }
        previewUrl.value = '';
        blob.value = null;
        busy.value = false;

        if (document.fonts?.ready) {
            await document.fonts.ready;
        }
        await render();

        const file = blob.value
            ? new File([blob.value], fileName(), { type: 'image/png' })
            : null;

        canShareFiles.value =
            typeof navigator !== 'undefined' &&
            typeof navigator.share === 'function' &&
            !!file &&
            !!navigator.canShare?.({ files: [file] });

        canShareNative.value =
            typeof navigator !== 'undefined' && typeof navigator.share === 'function';

        canCopyImage.value =
            typeof ClipboardItem !== 'undefined' &&
            !!navigator.clipboard?.write &&
            !!blob.value;
    },
);

onBeforeUnmount(() => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
    window.clearTimeout(copyTimer);
    window.clearTimeout(imageCopyTimer);
});
</script>
