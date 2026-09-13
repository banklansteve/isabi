<template>
    <div class="flex min-h-0 flex-1 flex-col border-t border-amber-200/80 bg-amber-50/80">
        <div class="px-4 pt-3">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-amber-800/70">
                Internal notes
            </p>
            <p class="pt-1 text-[11px] font-medium text-amber-800/55">
                Grouped by day. Never shown to the artisan.
            </p>
        </div>

        <ul class="min-h-0 flex-1 space-y-1.5 overflow-y-auto px-3 py-3">
            <li v-if="!sessions.length" class="px-1 py-2 text-[12px] font-medium text-amber-800/50">
                No notes yet.
            </li>
            <li
                v-for="session in sessions"
                :key="session.key"
                class="overflow-hidden rounded-xl bg-white/80 ring-1 ring-amber-200/70"
            >
                <button
                    type="button"
                    class="flex w-full items-center gap-2.5 px-3 py-2.5 text-left transition-colors hover:bg-amber-50/80"
                    :aria-expanded="openKey === session.key"
                    @click="toggle(session.key)"
                >
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-800">
                        <i class="ti ti-calendar-event text-sm" aria-hidden="true" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-[13px] font-bold text-ink">{{ session.label }}</span>
                        <span class="mt-0.5 block truncate text-[11px] font-medium text-ink/40">
                            {{ session.summary }}
                        </span>
                    </span>
                    <span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-extrabold tabular-nums text-amber-800">
                        {{ session.notes.length }}
                    </span>
                    <i
                        class="ti text-sm text-ink/30 transition-transform"
                        :class="openKey === session.key ? 'ti-chevron-up' : 'ti-chevron-down'"
                        aria-hidden="true"
                    />
                </button>

                <ul
                    v-if="openKey === session.key"
                    class="space-y-2 border-t border-amber-100 px-3 py-2.5"
                >
                    <li
                        v-for="note in session.notes"
                        :key="note.id"
                        class="rounded-lg bg-amber-50/60 px-2.5 py-2"
                    >
                        <p class="text-[13px] font-medium leading-relaxed text-ink">{{ note.body }}</p>
                        <p class="mt-1 text-[10px] font-semibold text-ink/35">
                            {{ note.author }} · {{ note.when }}
                        </p>
                    </li>
                </ul>
            </li>
        </ul>

        <form class="border-t border-amber-200/70 p-3" @submit.prevent="$emit('add')">
            <textarea
                :value="draft"
                rows="2"
                placeholder="Add a note for the team…"
                class="w-full resize-none rounded-xl border border-amber-200 bg-white px-3 py-2 text-[13px] outline-none focus:ring-4 focus:ring-amber-100"
                @input="$emit('update:draft', $event.target.value)"
            />
            <button
                type="submit"
                class="mt-2 w-full rounded-xl bg-amber-800 px-3 py-2 text-[12px] font-semibold text-white disabled:opacity-40"
                :disabled="!draft.trim()"
            >
                Add note
            </button>
        </form>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    notes: { type: Array, default: () => [] },
    draft: { type: String, default: '' },
});

defineEmits(['update:draft', 'add']);

const openKey = ref('');

const dayKey = (iso) => {
    if (!iso) {
        return 'unknown';
    }
    try {
        const date = new Date(iso);
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');

        return `${y}-${m}-${d}`;
    } catch {
        return 'unknown';
    }
};

const dayLabel = (key) => {
    if (key === 'unknown') {
        return 'Undated';
    }

    try {
        const date = new Date(`${key}T12:00:00`);
        const today = new Date();
        const todayKey = dayKey(today.toISOString());
        const yesterday = new Date(today);
        yesterday.setDate(today.getDate() - 1);
        const yesterdayKey = dayKey(yesterday.toISOString());

        if (key === todayKey) {
            return 'Today';
        }
        if (key === yesterdayKey) {
            return 'Yesterday';
        }

        return new Intl.DateTimeFormat('en-GB', {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
            year: date.getFullYear() !== today.getFullYear() ? 'numeric' : undefined,
        }).format(date);
    } catch {
        return key;
    }
};

const sessions = computed(() => {
    const map = new Map();

    for (const note of props.notes || []) {
        const key = dayKey(note.iso);
        if (!map.has(key)) {
            map.set(key, []);
        }
        map.get(key).push(note);
    }

    return [...map.entries()]
        .sort((a, b) => b[0].localeCompare(a[0]))
        .map(([key, notes]) => {
            const authors = [...new Set(notes.map((n) => n.author).filter(Boolean))];
            const count = notes.length;
            const authorBit = authors.length <= 2
                ? authors.join(', ')
                : `${authors[0]} +${authors.length - 1}`;

            return {
                key,
                label: dayLabel(key),
                summary: `${count} note${count === 1 ? '' : 's'}${authorBit ? ` · ${authorBit}` : ''}`,
                notes: [...notes].sort((a, b) => String(a.iso || '').localeCompare(String(b.iso || ''))),
            };
        });
});

const toggle = (key) => {
    openKey.value = openKey.value === key ? '' : key;
};

watch(
    sessions,
    (value) => {
        if (!value.length) {
            openKey.value = '';
            return;
        }
        // Keep the newest session open by default; preserve selection if still present.
        if (!value.some((session) => session.key === openKey.value)) {
            openKey.value = value[0].key;
        }
    },
    { immediate: true },
);
</script>
