<template>
    <Head title="Account" />

    <AdminChrome title="Account" />

    <div class="mx-auto max-w-5xl">
        <section
            class="relative mb-6 overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-5 py-7 shadow-premium-ink sm:mb-8 sm:px-7 sm:py-8"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(45%_55%_at_100%_100%,rgba(255,106,61,0.14),transparent_50%)]"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.18]"
                style="
                    background-image: radial-gradient(rgba(255, 255, 255, 0.1) 0.7px, transparent 0.7px);
                    background-size: 18px 18px;
                "
                aria-hidden="true"
            />

            <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    <UserAvatar
                        :src="localAvatarUrl"
                        :initials="localAccount.initials || 'I'"
                        :alt="displayName"
                        size="hero"
                        class="bg-white shadow-sm ring-2 ring-white/20"
                    />
                    <div class="min-w-0">
                        <p class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/55">
                            <span class="h-1.5 w-1.5 rounded-full bg-coral" aria-hidden="true" />
                            {{ localAccount.user_type_label || 'Staff account' }}
                        </p>
                        <h1 class="mt-2 truncate font-editorial text-[2rem] font-semibold leading-[1.1] tracking-tight text-white sm:text-[2.35rem]">
                            {{ displayName }}
                        </h1>
                        <p class="mt-2 max-w-md text-sm font-medium leading-relaxed text-white/65">
                            {{ localAccount.user_type_description }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                    <Link
                        :href="route('admin.dashboard')"
                        class="tap-target inline-flex items-center justify-center gap-2 rounded-2xl bg-coral px-5 py-3 text-sm font-bold text-white shadow-[0_12px_28px_-10px_rgba(255,106,61,0.55)] transition-colors hover:bg-coral-deep"
                    >
                        Done
                        <i class="ti ti-arrow-right" aria-hidden="true" />
                    </Link>
                </div>
            </div>
        </section>

        <nav
            class="mb-5 flex gap-1.5 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] sm:mb-6 sm:justify-center [&::-webkit-scrollbar]:hidden"
            aria-label="Account sections"
        >
            <Link
                :href="route('admin.account')"
                class="tap-target inline-flex shrink-0 items-center gap-2 rounded-full px-3.5 py-2 text-xs font-bold transition-all duration-200"
                :class="tab === 'profile' ? 'bg-base-action text-white shadow-sm' : 'bg-white text-ink/55 ring-1 ring-ink/[0.06] hover:text-base-action'"
            >
                <i class="ti ti-user-circle text-sm" aria-hidden="true" />
                Profile
            </Link>
            <Link
                :href="route('admin.account', { tab: 'password' })"
                class="tap-target inline-flex shrink-0 items-center gap-2 rounded-full px-3.5 py-2 text-xs font-bold transition-all duration-200"
                :class="tab === 'password' ? 'bg-base-action text-white shadow-sm' : 'bg-white text-ink/55 ring-1 ring-ink/[0.06] hover:text-base-action'"
            >
                <i class="ti ti-lock text-sm" aria-hidden="true" />
                Security
            </Link>
        </nav>

        <div v-if="tab === 'profile'" class="space-y-5 sm:space-y-6">
            <section class="overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]">
                <div class="relative overflow-hidden px-5 py-5 sm:px-8 sm:py-6">
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427]" aria-hidden="true" />
                    <div
                        class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.14),transparent_55%)]"
                        aria-hidden="true"
                    />
                    <div class="relative flex items-start gap-3.5">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/12 text-lg text-white ring-1 ring-white/15">
                            <i class="ti ti-camera" aria-hidden="true" />
                        </span>
                        <div class="min-w-0">
                            <h2 class="font-editorial text-xl font-semibold tracking-tight text-white sm:text-[1.35rem]">
                                Profile photo
                            </h2>
                            <p class="mt-1 text-sm font-medium leading-relaxed text-white/65">
                                Shown in the console header, chat, and anywhere teammates see you.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-6 lg:p-8">
                    <div class="flex flex-col gap-4 rounded-2xl bg-pale p-4 ring-1 ring-ink/[0.05] sm:flex-row sm:items-center sm:p-5">
                        <UserAvatar
                            :src="localAvatarUrl"
                            :initials="localAccount.initials || 'I'"
                            :alt="displayName"
                            size="xl"
                            class="shadow-sm ring-2 ring-white"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-ink/40">Your photo</p>
                            <p class="mt-1 text-sm font-medium text-ink/55">
                                {{
                                    localAvatarUrl
                                        ? 'Looking good — teammates see this across the console.'
                                        : 'Add a clear photo so teammates recognise you in support and chat.'
                                }}
                            </p>
                            <label
                                class="tap-target mt-3 inline-flex cursor-pointer items-center gap-2 rounded-xl bg-base-action px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-base-hover"
                            >
                                <i class="ti ti-upload" aria-hidden="true" />
                                {{ avatarBusy ? 'Uploading…' : localAvatarUrl ? 'Change photo' : 'Upload photo' }}
                                <input
                                    type="file"
                                    accept="image/*"
                                    class="sr-only"
                                    :disabled="avatarBusy"
                                    @change="onAvatarPick"
                                />
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-ink/[0.06] px-5 py-4 sm:px-8">
                    <div>
                        <h2 class="text-[15px] font-bold tracking-tight text-ink">Account details</h2>
                        <p class="mt-0.5 text-[13px] font-medium text-ink/45">Identity, access type, and sign-in history.</p>
                    </div>
                    <button
                        v-if="!editingName"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-base-action px-3 py-2 text-[12px] font-bold text-white transition-colors duration-150 hover:bg-base-hover"
                        @click="startNameEdit"
                    >
                        <i class="ti ti-pencil" aria-hidden="true" />
                        Edit name
                    </button>
                </div>

                <div v-if="editingName" class="border-b border-ink/[0.06] bg-pale/40 p-4 sm:p-6 lg:px-8">
                    <form class="space-y-4" @submit.prevent="saveName">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="block">
                                <span class="text-[11px] font-bold uppercase tracking-[0.1em] text-ink/40">First name</span>
                                <input
                                    v-model="nameForm.first_name"
                                    type="text"
                                    required
                                    autocomplete="given-name"
                                    class="mt-1.5 w-full rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                                    :class="nameForm.errors.first_name ? 'border-red-300 focus:border-red-400 focus:ring-red-100' : ''"
                                />
                                <p v-if="nameForm.errors.first_name" class="mt-1 text-[12px] font-semibold text-coral-deep">
                                    {{ nameForm.errors.first_name }}
                                </p>
                            </label>
                            <label class="block">
                                <span class="text-[11px] font-bold uppercase tracking-[0.1em] text-ink/40">Last name</span>
                                <input
                                    v-model="nameForm.last_name"
                                    type="text"
                                    required
                                    autocomplete="family-name"
                                    class="mt-1.5 w-full rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                                    :class="nameForm.errors.last_name ? 'border-red-300 focus:border-red-400 focus:ring-red-100' : ''"
                                />
                                <p v-if="nameForm.errors.last_name" class="mt-1 text-[12px] font-semibold text-coral-deep">
                                    {{ nameForm.errors.last_name }}
                                </p>
                            </label>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-base-action px-4 py-2.5 text-[13px] font-bold text-white transition-colors duration-150 hover:bg-base-hover disabled:opacity-60"
                                :disabled="nameBusy"
                            >
                                <i
                                    class="text-base"
                                    :class="nameBusy ? 'ti ti-loader-2 animate-spin' : 'ti ti-check'"
                                    aria-hidden="true"
                                />
                                {{ nameBusy ? 'Saving…' : 'Save name' }}
                            </button>
                            <button
                                type="button"
                                class="rounded-xl px-4 py-2.5 text-[13px] font-semibold text-ink/50 transition-colors hover:bg-white hover:text-ink"
                                :disabled="nameBusy"
                                @click="cancelNameEdit"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>

                <dl class="grid gap-3 p-4 sm:grid-cols-2 sm:p-6 lg:p-8">
                    <div
                        v-for="row in detailRows"
                        :key="row.label"
                        class="rounded-2xl bg-pale px-4 py-3.5 ring-1 ring-ink/[0.04]"
                        :class="row.span ? 'sm:col-span-2' : ''"
                    >
                        <dt class="text-[11px] font-bold uppercase tracking-[0.1em] text-ink/40">{{ row.label }}</dt>
                        <dd class="mt-1.5 text-sm font-semibold leading-relaxed text-ink" :class="row.mono ? 'font-mono tracking-[0.06em]' : ''">
                            {{ row.value }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]">
                <div class="border-b border-ink/[0.06] px-5 py-4 sm:px-8">
                    <h2 class="text-[15px] font-bold tracking-tight text-ink">Assigned roles</h2>
                    <p class="mt-0.5 text-[13px] font-medium text-ink/45">
                        Every role currently on your account. Super Admin access is shown when applicable.
                    </p>
                </div>
                <div class="p-4 sm:p-6 lg:p-8">
                    <ul v-if="account.roles?.length" class="space-y-2">
                        <li
                            v-for="role in account.roles"
                            :key="role.id"
                            class="flex items-center justify-between gap-3 rounded-2xl bg-pale px-4 py-3 ring-1 ring-ink/[0.04]"
                        >
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-ink">{{ role.name }}</p>
                                <p v-if="role.system" class="mt-0.5 text-[12px] font-medium text-ink/45">Built-in platform access</p>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide"
                                :class="role.system ? 'bg-deep/10 text-deep' : 'bg-white text-ink/50 ring-1 ring-ink/[0.06]'"
                            >
                                {{ role.system ? 'System' : 'Custom' }}
                            </span>
                        </li>
                    </ul>
                    <p v-else class="rounded-2xl bg-pale px-4 py-4 text-[13px] font-medium text-ink/50">
                        No custom roles assigned yet. Your user type still controls console access.
                    </p>

                    <div v-if="account.duties?.length" class="mt-5 border-t border-ink/[0.06] pt-5">
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-ink/35">Active duties</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <span
                                v-for="duty in account.duties"
                                :key="duty.slug"
                                class="inline-flex items-center gap-1.5 rounded-full bg-tint px-2.5 py-1 text-[12px] font-semibold text-deep"
                            >
                                <i v-if="duty.icon" :class="duty.icon" class="text-sm" aria-hidden="true" />
                                {{ duty.name }}
                            </span>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <section
            v-else
            class="overflow-hidden rounded-[1.5rem] bg-white shadow-premium ring-1 ring-ink/[0.06]"
        >
            <div class="relative overflow-hidden px-5 py-5 sm:px-8 sm:py-6">
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427]" aria-hidden="true" />
                <div class="relative flex items-start gap-3.5">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/12 text-lg text-white ring-1 ring-white/15">
                        <i class="ti ti-lock" aria-hidden="true" />
                    </span>
                    <div class="min-w-0">
                        <h2 class="font-editorial text-xl font-semibold tracking-tight text-white sm:text-[1.35rem]">
                            Password & sign-in
                        </h2>
                        <p class="mt-1 text-sm font-medium leading-relaxed text-white/65">
                            Change the password you use to access this console.
                        </p>
                    </div>
                </div>
            </div>
            <div class="p-4 sm:p-6 lg:p-8">
                <UpdatePasswordForm />
            </div>
        </section>
    </div>
</template>

<script setup>
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import UserAvatar from '@/Components/App/UserAvatar.vue';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';
import { toast } from '@/utils/adminRange';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    tab: { type: String, default: 'profile' },
    account: { type: Object, required: true },
});

const localAccount = ref({ ...props.account });
const localAvatarUrl = ref(props.account.avatar_url || '');
const avatarBusy = ref(false);
const editingName = ref(false);
const nameBusy = ref(false);
const nameForm = reactive({
    first_name: '',
    last_name: '',
    errors: {},
});

watch(
    () => props.account,
    (value) => {
        localAccount.value = { ...value };
        localAvatarUrl.value = value.avatar_url || '';
    },
    { deep: true },
);

const displayName = computed(() => localAccount.value.name || 'Your account');

const detailRows = computed(() => [
    { label: 'Full name', value: localAccount.value.name || '—' },
    { label: 'Email', value: localAccount.value.email || '—', span: true },
    { label: 'User type', value: localAccount.value.user_type_label || '—' },
    { label: 'Account status', value: localAccount.value.staff_status_label || '—' },
    { label: 'Staff ID', value: localAccount.value.uid || '—', mono: true },
    { label: 'Last sign-in', value: localAccount.value.last_login || 'Never' },
    { label: 'Joined', value: localAccount.value.joined || '—' },
]);

const startNameEdit = () => {
    nameForm.first_name = localAccount.value.first_name || '';
    nameForm.last_name = localAccount.value.last_name || '';
    nameForm.errors = {};
    editingName.value = true;
};

const cancelNameEdit = () => {
    editingName.value = false;
    nameForm.errors = {};
};

const applyAccount = (account) => {
    if (!account) {
        return;
    }

    localAccount.value = { ...localAccount.value, ...account };
    localAvatarUrl.value = account.avatar_url || localAvatarUrl.value;
};

const saveName = async () => {
    nameBusy.value = true;
    nameForm.errors = {};

    try {
        const { data } = await axios.patch(
            route('admin.account.profile'),
            {
                first_name: nameForm.first_name,
                last_name: nameForm.last_name,
            },
            { headers: { Accept: 'application/json' } },
        );
        toast(data.toast);
        applyAccount(data.account);
        editingName.value = false;
        router.reload({ only: ['auth'], preserveScroll: true });
    } catch (error) {
        nameForm.errors = error.response?.data?.errors || {};
        toast({
            type: 'error',
            title: 'Couldn’t save',
            message: error.response?.data?.message || 'Check your first and last name and try again.',
        });
    } finally {
        nameBusy.value = false;
    }
};

const onAvatarPick = async (event) => {
    const file = event.target.files?.[0];
    event.target.value = '';

    if (!file) {
        return;
    }

    avatarBusy.value = true;
    const body = new FormData();
    body.append('avatar', file);

    try {
        const { data } = await axios.post(route('admin.account.avatar'), body, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        toast(data.toast);
        applyAccount(data.account);
    } catch (error) {
        toast({
            type: 'error',
            title: 'Upload failed',
            message: error.response?.data?.message || 'Could not upload your photo. Try a JPG or PNG under 5 MB.',
        });
    } finally {
        avatarBusy.value = false;
    }
};
</script>
