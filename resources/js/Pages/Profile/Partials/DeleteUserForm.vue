<template>
    <section>
        <Transition
            mode="out-in"
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
        >
            <div v-if="!editing" key="view" class="space-y-5">
                <div class="flex justify-end">
                    <button
                        type="button"
                        class="tap-target inline-flex items-center gap-2 rounded-2xl bg-coral px-4 py-2.5 text-sm font-bold text-white shadow-[0_10px_24px_-10px_rgba(255,106,61,0.45)] transition-colors hover:bg-coral-deep"
                        @click="editing = true"
                    >
                        <i class="ti ti-trash" aria-hidden="true" />
                        Delete account
                    </button>
                </div>

                <div class="rounded-2xl bg-pale px-4 py-4 ring-1 ring-ink/[0.05]">
                    <p class="text-sm font-bold text-ink">Your account is active</p>
                    <p class="mt-1 text-sm font-medium leading-relaxed text-ink/50">
                        Deleting removes your public page, work log, and reviews permanently. Download
                        anything you need first.
                    </p>
                </div>
            </div>

            <div v-else key="edit" class="space-y-5">
                <div class="rounded-2xl border border-coral/20 bg-coral/[0.06] px-4 py-4">
                    <p class="text-sm font-bold text-ink">Delete this account?</p>
                    <p class="mt-1 text-sm font-medium leading-relaxed text-ink/55">
                        This can’t be undone. Type
                        <span class="font-bold text-coral-deep">Delete</span>
                        below, then confirm with your password.
                    </p>
                </div>

                <FormTextInput
                    id="delete-confirmation"
                    v-model="form.confirmation"
                    label='Type “Delete” to confirm'
                    placeholder="Delete"
                    autocomplete="off"
                    :error="form.errors.confirmation"
                />

                <FormPasswordInput
                    id="delete-account-password"
                    v-model="form.password"
                    label="Password"
                    placeholder="Your password"
                    autocomplete="current-password"
                    :error="form.errors.password"
                    @keyup.enter="deleteUser"
                />

                <div class="flex flex-wrap items-center gap-3">
                    <FormButton
                        variant="primary"
                        label="Permanently delete"
                        class="!bg-coral hover:!bg-coral-deep !shadow-[0_12px_28px_-10px_rgba(255,106,61,0.45)]"
                        :disabled="!canDelete"
                        :loading="form.processing"
                        loading-label="Deleting…"
                        @click="deleteUser"
                    />
                    <FormButton type="button" variant="ghost" label="Cancel" @click="cancelEditing" />
                </div>
            </div>
        </Transition>
    </section>
</template>

<script setup>
import FormButton from '@/Components/Form/FormButton.vue';
import FormPasswordInput from '@/Components/Form/FormPasswordInput.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({
    showHeader: { type: Boolean, default: true },
});

const editing = ref(false);

const form = useForm({
    password: '',
    confirmation: '',
});

const canDelete = computed(() => form.confirmation === 'Delete' && form.password.length > 0);

const cancelEditing = () => {
    form.clearErrors();
    form.reset();
    editing.value = false;
};

const deleteUser = () => {
    if (!canDelete.value) return;

    form.delete(route('profile.destroy'), {
        onFinish: () => form.reset(),
    });
};
</script>
