<template>
    <Head title="Account" />

    <AdminChrome title="Account" />

    <div class="mx-auto max-w-2xl">
        <section class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
            <div class="flex items-start gap-4 px-5 py-5 sm:px-6 sm:py-6">
                <UserAvatar
                    :src="user?.avatar_url"
                    :initials="user?.initials || 'I'"
                    :alt="user?.name || 'Profile'"
                    size="hero"
                    class="shadow-sm ring-2 ring-white"
                />
                <div class="min-w-0 flex-1 pt-1">
                    <h1 class="truncate text-xl font-bold tracking-tight text-ink sm:text-2xl">
                        {{ user?.name }}
                    </h1>
                    <p class="mt-1 truncate text-[13px] font-medium text-ink/45">
                        {{ user?.email }}
                    </p>
                    <p v-if="roleSummary" class="mt-2 text-[12px] font-semibold text-ink/40">
                        {{ roleSummary }}
                    </p>
                </div>
            </div>

            <div class="border-t border-ink/[0.06] px-4 pt-3 sm:px-5">
                <nav class="flex gap-1 rounded-xl bg-pale p-1" aria-label="Account">
                    <Link
                        :href="route('admin.account')"
                        class="flex-1 rounded-lg px-3 py-2 text-center text-[13px] font-semibold transition-colors"
                        :class="tab === 'profile' ? 'bg-white text-ink shadow-sm' : 'text-ink/45 hover:text-ink'"
                    >
                        Profile
                    </Link>
                    <Link
                        :href="route('admin.account', { tab: 'password' })"
                        class="flex-1 rounded-lg px-3 py-2 text-center text-[13px] font-semibold transition-colors"
                        :class="tab === 'password' ? 'bg-white text-ink shadow-sm' : 'text-ink/45 hover:text-ink'"
                    >
                        Settings
                    </Link>
                </nav>
            </div>

            <div v-if="tab === 'profile'" class="px-5 py-5 sm:px-6 sm:py-6">
                <dl class="divide-y divide-ink/[0.06]">
                    <div class="flex items-baseline justify-between gap-4 py-3 first:pt-0">
                        <dt class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Name</dt>
                        <dd class="text-sm font-semibold text-ink">{{ user?.name }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4 py-3">
                        <dt class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Email</dt>
                        <dd class="truncate text-sm font-semibold text-ink">{{ user?.email }}</dd>
                    </div>
                    <div v-if="user?.uid" class="flex items-baseline justify-between gap-4 py-3">
                        <dt class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Staff ID</dt>
                        <dd class="font-mono text-[13px] font-semibold tracking-[0.08em] text-ink">{{ user.uid }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4 py-3 last:pb-0">
                        <dt class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Role</dt>
                        <dd class="text-right text-sm font-semibold text-ink">{{ roleSummary || 'Operations' }}</dd>
                    </div>
                </dl>
            </div>

            <div v-else class="px-5 py-5 sm:px-6 sm:py-6">
                <h2 class="text-[15px] font-bold tracking-tight text-ink">Password</h2>
                <p class="mt-1 mb-5 text-[13px] font-medium leading-relaxed text-ink/45">
                    Change the password you use to sign in to this console.
                </p>
                <UpdatePasswordForm />
            </div>
        </section>
    </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import UserAvatar from '@/Components/App/UserAvatar.vue';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    tab: { type: String, default: 'profile' },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const roleSummary = computed(() => page.props.ops_inbox?.role_summary || '');
</script>
