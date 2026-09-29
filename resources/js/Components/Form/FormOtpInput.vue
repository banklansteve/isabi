<template>
    <div>
        <p v-if="label" class="mb-2 text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">
            {{ label }}
        </p>
        <div
            class="flex items-center justify-between gap-1.5 sm:gap-2.5"
            role="group"
            :aria-label="label || 'Verification code'"
        >
            <input
                v-for="(digit, index) in digits"
                :id="index === 0 ? inputId : undefined"
                :key="index"
                :ref="(el) => setInputRef(el, index)"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                autocomplete="one-time-code"
                maxlength="1"
                :aria-label="`Digit ${index + 1} of ${length}`"
                class="otp-box h-12 w-10 rounded-xl border bg-white text-center font-display text-xl font-extrabold tabular-nums text-ink shadow-[0_1px_0_rgba(11,31,58,0.04)] outline-none transition-all duration-150 sm:h-14 sm:w-12 sm:rounded-2xl sm:text-2xl"
                :class="boxClass(index)"
                :value="digit"
                @input="onInput($event, index)"
                @keydown="onKeydown($event, index)"
                @paste="onPaste"
                @focus="onFocus(index)"
            />
        </div>
        <p v-if="error" class="mt-2 text-[12px] font-semibold text-rose-600">{{ error }}</p>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    length: { type: Number, default: 6 },
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    autofocus: { type: Boolean, default: true },
    inputId: { type: String, default: 'otp-0' },
});

const emit = defineEmits(['update:modelValue', 'complete']);

const inputs = ref([]);
const focused = ref(0);

const digits = computed(() => {
    const raw = String(props.modelValue || '').replace(/\D/g, '').slice(0, props.length);
    return Array.from({ length: props.length }, (_, i) => raw[i] || '');
});

const setInputRef = (el, index) => {
    if (el) {
        inputs.value[index] = el;
    }
};

const boxClass = (index) => {
    if (props.error) {
        return 'border-rose-300 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/15';
    }
    if (digits.value[index]) {
        return 'border-base-action/35 bg-[#F7FAFF] focus:border-base-action focus:ring-4 focus:ring-base/20';
    }
    if (focused.value === index) {
        return 'border-base-action ring-4 ring-base/20';
    }
    return 'border-ink/10 hover:border-ink/20 focus:border-base-action focus:ring-4 focus:ring-base/20';
};

const emitValue = (next) => {
    const cleaned = String(next || '')
        .replace(/\D/g, '')
        .slice(0, props.length);
    emit('update:modelValue', cleaned);
    if (cleaned.length === props.length) {
        emit('complete', cleaned);
    }
};

const focusAt = async (index) => {
    const target = Math.max(0, Math.min(props.length - 1, index));
    focused.value = target;
    await nextTick();
    inputs.value[target]?.focus();
    inputs.value[target]?.select?.();
};

const onInput = (event, index) => {
    const raw = String(event.target.value || '').replace(/\D/g, '');
    if (!raw) {
        const next = digits.value.slice();
        next[index] = '';
        emitValue(next.join(''));
        return;
    }

    // Handle autofill / multi-char paste into a single box.
    if (raw.length > 1) {
        emitValue(raw);
        focusAt(Math.min(props.length - 1, raw.length));
        return;
    }

    const next = digits.value.slice();
    next[index] = raw[0];
    emitValue(next.join(''));
    if (index < props.length - 1) {
        focusAt(index + 1);
    }
};

const onKeydown = (event, index) => {
    if (event.key === 'Backspace') {
        if (digits.value[index]) {
            const next = digits.value.slice();
            next[index] = '';
            emitValue(next.join(''));
            return;
        }
        if (index > 0) {
            event.preventDefault();
            const next = digits.value.slice();
            next[index - 1] = '';
            emitValue(next.join(''));
            focusAt(index - 1);
        }
        return;
    }

    if (event.key === 'ArrowLeft' && index > 0) {
        event.preventDefault();
        focusAt(index - 1);
    }
    if (event.key === 'ArrowRight' && index < props.length - 1) {
        event.preventDefault();
        focusAt(index + 1);
    }
};

const onPaste = (event) => {
    event.preventDefault();
    const text = event.clipboardData?.getData('text') || '';
    const cleaned = text.replace(/\D/g, '').slice(0, props.length);
    if (!cleaned) return;
    emitValue(cleaned);
    focusAt(Math.min(props.length - 1, cleaned.length));
};

const onFocus = (index) => {
    focused.value = index;
};

watch(
    () => props.modelValue,
    (value) => {
        const cleaned = String(value || '').replace(/\D/g, '').slice(0, props.length);
        if (cleaned !== value) {
            emit('update:modelValue', cleaned);
        }
    },
);

onMounted(() => {
    if (props.autofocus) {
        focusAt(0);
    }
});

defineExpose({ focusAt });
</script>

<style scoped>
.otp-box {
    caret-color: #1a4fb5;
}
</style>
