<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold tracking-tight text-ink">
                Account settings
            </h2>
        </template>

        <div class="space-y-6">
            <div class="mx-auto max-w-3xl space-y-6">
                <div
                    class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.06] sm:p-8"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        :profile="profile"
                        :locations="locations"
                        :trades="trades"
                        class="max-w-xl"
                    />
                </div>

                <div class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.06] sm:p-8">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div class="rounded-2xl bg-white p-4 shadow-premium ring-1 ring-ink/[0.06] sm:p-8">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: { type: Boolean, default: false },
    status: { type: String, default: null },
    profile: { type: Object, required: true },
    locations: { type: Object, default: () => ({}) },
    trades: { type: Array, default: () => [] },
});
</script>
