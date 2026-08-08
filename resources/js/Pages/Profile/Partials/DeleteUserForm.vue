<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-bold tracking-tight text-ink">
                Delete account
            </h2>
            <p class="mt-1 text-sm font-medium text-ink/50">
                Once your account is deleted, all of its resources and data will
                be permanently deleted. Before deleting your account, please
                download any data or information that you wish to retain.
            </p>
        </header>

        <FormButton
            variant="primary"
            label="Delete account"
            class="!bg-coral hover:!bg-coral-deep !shadow-[0_12px_28px_-10px_rgba(255,106,61,0.45)]"
            @click="confirmUserDeletion"
        />

        <AppModal
            :show="confirmingUserDeletion"
            title="Delete your account?"
            description="This can’t be undone. Enter your password to confirm permanent deletion."
            icon="ti ti-alert-triangle"
            icon-tone="coral"
            size="md"
            @close="closeModal"
        >
            <div>
                <FormPasswordInput
                    id="delete-account-password"
                    v-model="form.password"
                    label="Password"
                    placeholder="Your password"
                    autocomplete="current-password"
                    :error="form.errors.password"
                    @keyup.enter="deleteUser"
                />
            </div>

            <template #footer>
                <FormButton
                    variant="secondary"
                    label="Cancel"
                    class="sm:min-w-[7rem]"
                    @click="closeModal"
                />
                <FormButton
                    variant="primary"
                    label="Delete account"
                    class="!bg-coral hover:!bg-coral-deep sm:min-w-[9rem]"
                    :loading="form.processing"
                    loading-label="Deleting…"
                    @click="deleteUser"
                />
            </template>
        </AppModal>
    </section>
</template>

<script setup>
import AppModal from '@/Components/App/AppModal.vue';
import FormButton from '@/Components/Form/FormButton.vue';
import FormPasswordInput from '@/Components/Form/FormPasswordInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => {
        document.getElementById('delete-account-password')?.focus();
    });
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>
