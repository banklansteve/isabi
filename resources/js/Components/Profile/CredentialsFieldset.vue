<template>
    <div>
        <div class="mb-2 flex items-end justify-between gap-3">
            <div>
                <p class="text-sm font-bold text-ink">Trade credentials</p>
                <p class="mt-0.5 text-xs font-medium text-ink/45">
                    Certificates, licences or registrations you hold.
                </p>
            </div>
            <span class="text-[11px] font-bold tabular-nums text-ink/40">
                {{ entries.length }}/{{ max }}
            </span>
        </div>

        <p
            class="mb-4 flex items-start gap-2 rounded-xl bg-amber-50 px-3.5 py-2.5 text-[11px] font-medium leading-relaxed text-amber-900 ring-1 ring-amber-200/70"
        >
            <i class="ti ti-alert-triangle mt-0.5 shrink-0 text-sm" aria-hidden="true" />
            <span>
                These show on your page labelled <strong class="font-bold">self-declared</strong>.
                Isabi does not verify them — only list what you can show a client on request.
            </span>
        </p>

        <!-- Current credentials -->
        <ul v-if="entries.length" class="mb-4 space-y-3">
            <li
                v-for="(entry, index) in entries"
                :key="index"
                class="rounded-2xl bg-pale p-3.5 ring-1 ring-ink/[0.06]"
            >
                <div class="flex items-start justify-between gap-3">
                    <p class="min-w-0 flex-1 text-sm font-bold leading-snug text-ink">
                        {{ entry.title }}
                    </p>
                    <button
                        type="button"
                        class="tap-target shrink-0 rounded-lg p-1 text-ink/35 transition-colors hover:text-coral"
                        :aria-label="`Remove ${entry.title}`"
                        @click="remove(index)"
                    >
                        <i class="ti ti-x text-base" aria-hidden="true" />
                    </button>
                </div>

                <p
                    v-if="errorFor(index, 'title')"
                    class="mt-1 text-xs font-semibold text-coral"
                >
                    {{ errorFor(index, 'title') }}
                </p>

                <div class="mt-3 grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                    <input
                        :value="entry.issuer"
                        type="text"
                        placeholder="Issued by"
                        maxlength="90"
                        class="w-full rounded-xl border border-ink/10 bg-white px-3 py-2 text-xs font-medium text-ink outline-none placeholder:text-ink/35 focus:border-base focus:ring-2 focus:ring-base/15"
                        :aria-label="`Issuer for ${entry.title}`"
                        @input="patch(index, 'issuer', $event.target.value)"
                    />
                    <div class="grid grid-cols-[1fr_5.5rem] gap-2.5">
                        <input
                            :value="entry.reference"
                            type="text"
                            placeholder="Cert / reg. no."
                            maxlength="60"
                            class="w-full rounded-xl border border-ink/10 bg-white px-3 py-2 text-xs font-medium text-ink outline-none placeholder:text-ink/35 focus:border-base focus:ring-2 focus:ring-base/15"
                            :aria-label="`Reference number for ${entry.title}`"
                            @input="patch(index, 'reference', $event.target.value)"
                        />
                        <input
                            :value="entry.year"
                            type="number"
                            placeholder="Year"
                            :min="1960"
                            :max="currentYear"
                            class="w-full rounded-xl border border-ink/10 bg-white px-3 py-2 text-xs font-medium tabular-nums text-ink outline-none placeholder:text-ink/35 focus:border-base focus:ring-2 focus:ring-base/15"
                            :aria-label="`Year obtained for ${entry.title}`"
                            @input="patch(index, 'year', $event.target.value)"
                        />
                    </div>
                </div>

                <p
                    v-if="errorFor(index, 'year')"
                    class="mt-1.5 text-xs font-semibold text-coral"
                >
                    {{ errorFor(index, 'year') }}
                </p>
            </li>
        </ul>

        <p v-if="errors?.credentials" class="mb-3 text-xs font-semibold text-coral">
            {{ errors.credentials }}
        </p>

        <!-- Catalogue -->
        <div class="rounded-2xl bg-white p-3.5 ring-1 ring-ink/[0.07]">
            <div class="relative">
                <i
                    class="ti ti-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-ink/35"
                    aria-hidden="true"
                />
                <input
                    v-model="query"
                    type="search"
                    placeholder="Search credentials…"
                    class="w-full rounded-xl border border-ink/10 bg-pale py-2.5 pe-3 ps-9 text-xs font-medium text-ink outline-none placeholder:text-ink/35 focus:border-base focus:bg-white focus:ring-2 focus:ring-base/15"
                    aria-label="Search trade credentials"
                />
            </div>

            <div v-if="filteredGroups.length" class="mt-3 space-y-3">
                <div v-for="group in filteredGroups" :key="group.label">
                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-ink/40">
                        {{ group.label }}
                    </p>
                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        <button
                            v-for="item in group.items"
                            :key="item.title"
                            type="button"
                            class="tap-target rounded-full bg-pale px-3 py-1.5 text-[11px] font-semibold text-ink/70 ring-1 ring-ink/[0.07] transition hover:bg-tint hover:text-deep disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="isFull || isAdded(item)"
                            @click="add(item)"
                        >
                            {{ isAdded(item) ? '✓' : '+' }} {{ item.title }}
                        </button>
                    </div>
                </div>
            </div>

            <p v-else class="mt-3 text-xs font-medium text-ink/40">
                No match. Add it as a custom credential below.
            </p>

            <div class="mt-3.5 flex items-stretch gap-2 border-t border-ink/[0.06] pt-3.5">
                <input
                    v-model="customTitle"
                    type="text"
                    maxlength="90"
                    placeholder="Other credential (e.g. Welding Level 2)"
                    class="min-w-0 flex-1 rounded-xl border border-ink/10 bg-pale px-3 py-2.5 text-xs font-medium text-ink outline-none placeholder:text-ink/35 focus:border-base focus:bg-white focus:ring-2 focus:ring-base/15"
                    aria-label="Custom credential name"
                    @keydown.enter.prevent="addCustom"
                />
                <button
                    type="button"
                    class="tap-target shrink-0 rounded-xl bg-tint px-4 text-xs font-bold text-deep ring-1 ring-base/15 transition hover:bg-base hover:text-white disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="isFull || !customTitle.trim()"
                    @click="addCustom"
                >
                    Add
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    catalogue: { type: Array, default: () => [] },
    max: { type: Number, default: 6 },
    errors: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:modelValue']);

const query = ref('');
const customTitle = ref('');
const currentYear = new Date().getFullYear();

/**
 * Edits are applied to a local copy first. Reading straight from the prop
 * would drop a change whenever two fields are written in the same tick —
 * browser autofill and fast tabbing both do that.
 */
const entries = ref([...(props.modelValue || [])]);

watch(
    () => props.modelValue,
    (value) => {
        if (value !== entries.value) {
            entries.value = [...(value || [])];
        }
    },
);

const isFull = computed(() => entries.value.length >= props.max);

const errorFor = (index, field) => props.errors?.[`credentials.${index}.${field}`] || '';

const isAdded = (item) =>
    entries.value.some((e) => e.title.toLowerCase() === item.title.toLowerCase());

const filteredGroups = computed(() => {
    const q = query.value.trim().toLowerCase();

    return props.catalogue
        .map((group) => ({
            label: group.label,
            items: q
                ? group.items.filter(
                      (item) =>
                          item.title.toLowerCase().includes(q) ||
                          String(item.issuer || '').toLowerCase().includes(q),
                  )
                : group.items,
        }))
        .filter((group) => group.items.length > 0);
});

const commit = (next) => {
    entries.value = next;
    emit('update:modelValue', next);
};

const add = (item) => {
    if (isFull.value || isAdded(item)) {
        return;
    }
    commit([
        ...entries.value,
        {
            title: item.title,
            issuer: item.issuer || '',
            reference: '',
            year: '',
        },
    ]);
};

const addCustom = () => {
    const title = customTitle.value.trim();
    if (!title || isFull.value || isAdded({ title })) {
        return;
    }
    commit([
        ...entries.value,
        { title: title.slice(0, 90), issuer: '', reference: '', year: '' },
    ]);
    customTitle.value = '';
};

const remove = (index) => {
    commit(entries.value.filter((_, i) => i !== index));
};

const patch = (index, field, value) => {
    commit(entries.value.map((entry, i) => (i === index ? { ...entry, [field]: value } : entry)));
};
</script>
