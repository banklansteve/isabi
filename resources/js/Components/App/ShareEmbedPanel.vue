<template>
    <section
        class="overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]"
        aria-labelledby="share-embed-heading"
    >
        <div class="border-b border-ink/[0.05] bg-gradient-to-r from-[#1A4FB5]/[0.04] to-white px-5 py-5 sm:px-6">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-base-action">Share &amp; embed</p>
            <h2 id="share-embed-heading" class="mt-1 font-editorial text-lg font-semibold tracking-tight text-ink">
                Put your proof anywhere
            </h2>
            <p class="mt-1 text-sm font-medium text-ink/45">
                Drop a widget on your website, link in bio, or Facebook — clean, branded, and always up to date.
            </p>
        </div>

        <div class="space-y-4 px-5 py-5 sm:px-6 sm:py-6">
            <div v-if="profileEmbedUrl" class="rounded-2xl bg-pale/70 p-4 ring-1 ring-ink/[0.05]">
                <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink/40">Profile widget</p>
                <p class="mt-1 text-xs font-medium text-ink/45">Best for website footers, link-in-bio tools, and Facebook tabs.</p>
                <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                    <code class="block min-w-0 flex-1 overflow-x-auto rounded-xl bg-white px-3 py-2.5 text-[11px] font-semibold text-ink/70 ring-1 ring-ink/[0.06]">
                        {{ profileSnippet }}
                    </code>
                    <button
                        type="button"
                        class="tap-target shrink-0 rounded-xl bg-base-action px-4 py-2.5 text-xs font-bold text-white hover:bg-base-hover"
                        @click="copy(profileSnippet, 'profile')"
                    >
                        {{ copied === 'profile' ? 'Copied' : 'Copy code' }}
                    </button>
                </div>
                <a
                    :href="profileEmbedUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-3 inline-flex items-center gap-1.5 text-xs font-bold text-base-action hover:underline"
                >
                    Preview widget
                    <i class="ti ti-external-link text-sm" aria-hidden="true" />
                </a>
            </div>

            <div v-if="jobEmbedUrl" class="rounded-2xl bg-pale/70 p-4 ring-1 ring-ink/[0.05]">
                <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink/40">This job widget</p>
                <p class="mt-1 text-xs font-medium text-ink/45">Embed one finished job with its reference and review.</p>
                <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                    <code class="block min-w-0 flex-1 overflow-x-auto rounded-xl bg-white px-3 py-2.5 text-[11px] font-semibold text-ink/70 ring-1 ring-ink/[0.06]">
                        {{ jobSnippet }}
                    </code>
                    <button
                        type="button"
                        class="tap-target shrink-0 rounded-xl bg-base-action px-4 py-2.5 text-xs font-bold text-white hover:bg-base-hover"
                        @click="copy(jobSnippet, 'job')"
                    >
                        {{ copied === 'job' ? 'Copied' : 'Copy code' }}
                    </button>
                </div>
            </div>

            <div v-if="reference" class="flex flex-wrap items-center gap-2 rounded-2xl bg-[#071427] px-4 py-3.5 text-white">
                <span class="text-[11px] font-bold uppercase tracking-[0.12em] text-white/45">Job reference</span>
                <span class="font-mono text-sm font-bold tracking-wide text-coral">{{ reference }}</span>
                <button
                    v-if="publicUrl"
                    type="button"
                    class="ms-auto tap-target rounded-xl bg-white/10 px-3 py-1.5 text-xs font-bold ring-1 ring-white/15 hover:bg-white/15"
                    @click="copy(publicUrl, 'link')"
                >
                    {{ copied === 'link' ? 'Link copied' : 'Copy link' }}
                </button>
            </div>

            <p class="text-[11px] font-medium leading-relaxed text-ink/40">
                Instagram: paste your page link in bio, or add the profile widget to a “link in bio” tool.
                Facebook: use the iframe code in a custom tab or pinned post.
            </p>
        </div>
    </section>
</template>

<script setup>
import { copyToClipboard } from '@/utils/clipboard';
import { computed, ref } from 'vue';

const props = defineProps({
    profileEmbedUrl: { type: String, default: '' },
    jobEmbedUrl: { type: String, default: '' },
    publicUrl: { type: String, default: '' },
    reference: { type: String, default: '' },
});

const copied = ref('');

const profileSnippet = computed(() =>
    props.profileEmbedUrl
        ? `<iframe src="${props.profileEmbedUrl}" width="360" height="520" style="border:0;border-radius:18px;max-width:100%" loading="lazy" title="Isabi profile"></iframe>`
        : '',
);

const jobSnippet = computed(() =>
    props.jobEmbedUrl
        ? `<iframe src="${props.jobEmbedUrl}" width="360" height="480" style="border:0;border-radius:18px;max-width:100%" loading="lazy" title="Isabi job reference"></iframe>`
        : '',
);

const copy = async (text, key) => {
    if (!text) {
        return;
    }
    const ok = await copyToClipboard(text);
    if (ok) {
        copied.value = key;
        window.setTimeout(() => {
            copied.value = '';
        }, 2200);
    }
};
</script>
