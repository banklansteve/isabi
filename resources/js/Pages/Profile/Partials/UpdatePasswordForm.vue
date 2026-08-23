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
                        class="tap-target inline-flex items-center gap-2 rounded-2xl bg-base-action px-4 py-2.5 text-sm font-bold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.45)] transition-colors hover:bg-base-hover"
                        @click="editing = true"
                    >
                        <i class="ti ti-lock-open" aria-hidden="true" />
                        Change password
                    </button>
                </div>

                <div class="flex items-start gap-3 rounded-2xl bg-pale px-4 py-4 ring-1 ring-ink/[0.05]">
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-lg text-deep shadow-sm ring-1 ring-ink/[0.06]"
                    >
                        <i class="ti ti-lock" aria-hidden="true" />
                    </span>
                    <div>
                        <p class="text-sm font-bold text-ink">Password</p>
                        <p class="mt-1 text-sm font-medium leading-relaxed text-ink/50">
                            Your password is hidden for safety. Change it anytime with a strong, unique
                            phrase.
                        </p>
                    </div>
                </div>
            </div>

            <form
                v-else
                key="edit"
                class="space-y-5"
                @submit.prevent="updatePassword"
            >
                <FormPasswordInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    label="Current password"
                    placeholder="Your current password"
                    autocomplete="current-password"
                    :error="form.errors.current_password"
                />

                <FormPasswordInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    label="New password"
                    placeholder="At least 8 characters"
                    autocomplete="new-password"
                    :error="form.errors.password"
                />

                <FormPasswordInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    label="Confirm new password"
                    placeholder="Repeat your new password"
                    autocomplete="new-password"
                    :error="form.errors.password_confirmation"
                />

                <div class="flex flex-wrap items-center gap-3 pt-1">
                    <FormButton
                        type="submit"
                        variant="primary"
                        label="Save password"
                        :loading="form.processing"
                        loading-label="Saving…"
                        icon-right="ti ti-check"
                    />
                    <FormButton type="button" variant="ghost" label="Cancel" @click="cancelEditing" />
                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 translate-y-1"
                        leave-active-class="transition duration-150 ease-in"
                        leave-to-class="opacity-0"
                    >
                        <p
                            v-if="form.recentlySuccessful"
                            class="text-sm font-semibold text-emerald-700"
                        >
                            Password updated.
                        </p>
                    </Transition>
                </div>
            </form>
        </Transition>
    </section>
</template>

<script setup>
import FormButton from '@/Components/Form/FormButton.vue';
import FormPasswordInput from '@/Components/Form/FormPasswordInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    showHeader: { type: Boolean, default: true },
});

const editing = ref(false);
const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const cancelEditing = () => {
    form.reset();
    form.clearErrors();
    editing.value = false;
};

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            editing.value = false;
        },
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus?.();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus?.();
            }
        },
    });
};
</script>
