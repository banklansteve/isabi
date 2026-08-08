<template>
    <FormField :id="id" :label="label" :hint="hint" :error="error">
        <template #default="{ id: fieldId, describedBy }">
            <div>
                <input
                    :id="fieldId"
                    ref="inputRef"
                    type="file"
                    class="sr-only"
                    :accept="accept"
                    :multiple="multiple"
                    :disabled="disabled"
                    :aria-describedby="describedBy"
                    @change="onPick"
                />

                <button
                    type="button"
                    class="tap-target group flex w-full items-center gap-4 rounded-2xl border border-dashed border-ink/15 bg-white px-4 py-4 text-left transition-colors duration-200 hover:border-base/40 hover:bg-pale/60 disabled:cursor-not-allowed disabled:opacity-50 sm:px-5"
                    :disabled="disabled"
                    @click="inputRef?.click()"
                >
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-tint text-lg text-deep transition-colors group-hover:bg-base group-hover:text-white">
                        <i class="ti ti-photo-plus" aria-hidden="true" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-ink">
                            {{ buttonLabel }}
                        </span>
                        <span class="mt-0.5 block text-xs font-medium text-ink/45">
                            {{ helpText }}
                        </span>
                    </span>
                </button>

                <ul
                    v-if="previews.length"
                    class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-4"
                >
                    <li
                        v-for="item in previews"
                        :key="item.id"
                        class="group relative aspect-square overflow-hidden rounded-xl bg-pale ring-1 ring-ink/10"
                    >
                        <img
                            v-if="item.kind === 'image'"
                            :src="item.url"
                            :alt="item.name"
                            class="h-full w-full object-cover"
                        />
                        <div
                            v-else
                            class="flex h-full w-full flex-col items-center justify-center gap-1 bg-ink/5 px-2 text-center"
                        >
                            <i class="ti ti-video text-xl text-ink/40" aria-hidden="true" />
                            <span class="line-clamp-2 text-[10px] font-medium text-ink/50">{{ item.name }}</span>
                        </div>
                        <button
                            type="button"
                            class="absolute right-1.5 top-1.5 flex h-7 w-7 items-center justify-center rounded-full bg-ink/80 text-white opacity-100 shadow-sm transition-opacity sm:opacity-0 sm:group-hover:opacity-100"
                            :aria-label="`Remove ${item.name}`"
                            @click="remove(item.id)"
                        >
                            <i class="ti ti-x text-sm" aria-hidden="true" />
                        </button>
                        <span
                            v-if="item.tooLarge"
                            class="absolute inset-x-0 bottom-0 bg-red-600/90 px-1 py-0.5 text-center text-[9px] font-semibold text-white"
                        >
                            Over 5MB
                        </span>
                    </li>
                </ul>
            </div>
        </template>
    </FormField>
</template>

<script setup>
import FormField from '@/Components/Form/FormField.vue';
import { onBeforeUnmount, ref, watch } from 'vue';

const model = defineModel({ type: Array, default: () => [] });

const props = defineProps({
    id: { type: String, default: '' },
    label: { type: String, default: '' },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
    buttonLabel: { type: String, default: 'Add photos or video' },
    helpText: { type: String, default: 'Images or videos up to 5MB each' },
    accept: { type: String, default: 'image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm' },
    multiple: { type: Boolean, default: true },
    maxFiles: { type: Number, default: 8 },
    maxBytes: { type: Number, default: 5 * 1024 * 1024 },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['change', 'previews']);

const inputRef = ref(null);
const previews = ref([]);
let idSeq = 0;

const syncModel = () => {
    model.value = previews.value.map((p) => p.file);
    emit('change', model.value);
    emit('previews', previews.value);
};

const onPick = (event) => {
    const files = Array.from(event.target.files || []);
    event.target.value = '';

    const room = Math.max(0, props.maxFiles - previews.value.length);
    const next = files.slice(0, room);

    next.forEach((file) => {
        const url = URL.createObjectURL(file);
        previews.value.push({
            id: ++idSeq,
            file,
            name: file.name,
            url,
            kind: file.type.startsWith('video/') ? 'video' : 'image',
            tooLarge: file.size > props.maxBytes,
        });
    });

    syncModel();
};

const remove = (id) => {
    const item = previews.value.find((p) => p.id === id);
    if (item) {
        URL.revokeObjectURL(item.url);
    }
    previews.value = previews.value.filter((p) => p.id !== id);
    syncModel();
};

watch(
    () => model.value,
    (files) => {
        if (!files?.length && previews.value.length) {
            previews.value.forEach((p) => URL.revokeObjectURL(p.url));
            previews.value = [];
        }
    },
);

onBeforeUnmount(() => {
    previews.value.forEach((p) => URL.revokeObjectURL(p.url));
});

defineExpose({
    previews,
    clear: () => {
        previews.value.forEach((p) => URL.revokeObjectURL(p.url));
        previews.value = [];
        syncModel();
    },
});
</script>
