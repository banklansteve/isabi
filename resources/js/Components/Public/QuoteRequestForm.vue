<template>
    <form class="space-y-4" @submit.prevent="submit">
        <div class="grid gap-3 sm:grid-cols-2">
            <label class="block">
                <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Your name</span>
                <input
                    v-model="form.name"
                    type="text"
                    required
                    autocomplete="name"
                    class="mt-1.5 w-full rounded-xl border border-ink/10 bg-[#F4F6FA] px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                />
            </label>
            <label class="block">
                <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Phone / WhatsApp</span>
                <input
                    v-model="form.phone"
                    type="tel"
                    required
                    autocomplete="tel"
                    class="mt-1.5 w-full rounded-xl border border-ink/10 bg-[#F4F6FA] px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
                />
            </label>
        </div>

        <label class="block">
            <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Job title / subject</span>
            <input
                v-model="form.subject"
                type="text"
                required
                placeholder="e.g. Rewire two-bedroom flat"
                class="mt-1.5 w-full rounded-xl border border-ink/10 bg-[#F4F6FA] px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
            />
        </label>

        <label class="block">
            <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Email</span>
            <input
                v-model="form.email"
                type="email"
                required
                autocomplete="email"
                class="mt-1.5 w-full rounded-xl border border-ink/10 bg-[#F4F6FA] px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
            />
        </label>

        <label class="block">
            <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">What do you need?</span>
            <textarea
                v-model="form.message"
                rows="3"
                placeholder="Briefly describe the job, location, and timeline…"
                class="mt-1.5 w-full rounded-xl border border-ink/10 bg-[#F4F6FA] px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:bg-white focus:ring-4 focus:ring-base/15"
            />
        </label>

        <p v-if="error" class="text-[13px] font-medium text-red-600">{{ error }}</p>

        <FormButton
            type="submit"
            variant="primary"
            label="Request a quote"
            :loading="busy"
            loading-label="Sending…"
            class="w-full sm:w-auto"
        />
    </form>
</template>

<script setup>
import FormButton from '@/Components/Form/FormButton.vue';
import { toast } from '@/utils/toast';
import axios from 'axios';
import { reactive, ref } from 'vue';

const props = defineProps({
    quoteUrl: { type: String, required: true },
    businessName: { type: String, default: 'the artisan' },
});

const emptyForm = () => ({
    name: '',
    phone: '',
    email: '',
    subject: '',
    message: '',
});

const form = reactive(emptyForm());
const busy = ref(false);
const error = ref('');

const resetForm = () => {
    Object.assign(form, emptyForm());
};

const submit = async () => {
    busy.value = true;
    error.value = '';

    try {
        const { data } = await axios.post(props.quoteUrl, { ...form });
        resetForm();

        const payload = data?.toast || {
            type: 'success',
            title: 'Request sent',
            message: data?.message || `Thanks — ${props.businessName} will reach out soon.`,
            duration: 5200,
        };

        toast(payload);
    } catch (e) {
        const errors = e.response?.data?.errors;
        if (errors?.email?.[0]) {
            error.value = errors.email[0];
        } else {
            error.value = e.response?.data?.message || 'Could not send your request. Try again.';
        }
        toast(error.value, 'error', 4800);
    } finally {
        busy.value = false;
    }
};
</script>
