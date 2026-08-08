<template>
    <section class="space-y-8">
        <header>
            <h2 class="text-lg font-bold tracking-tight text-ink">
                Profile information
            </h2>
            <p class="mt-1 text-sm font-medium text-ink/50">
                These details power your public page and review links.
            </p>
        </header>

        <form class="space-y-4" @submit.prevent="submitProfile">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormTextInput
                    id="first_name"
                    v-model="form.first_name"
                    label="First name"
                    icon="ti ti-user"
                    :error="form.errors.first_name"
                />
                <FormTextInput
                    id="last_name"
                    v-model="form.last_name"
                    label="Last name"
                    icon="ti ti-user"
                    :error="form.errors.last_name"
                />
            </div>

            <FormTextInput
                id="business_name"
                v-model="form.business_name"
                label="Business name"
                icon="ti ti-building-store"
                hint="Shown on your public page. Change your URL separately below."
                :error="form.errors.business_name"
            />

            <FormTextInput
                id="email"
                v-model="form.email"
                type="email"
                label="Email"
                icon="ti ti-mail"
                :error="form.errors.email"
            />

            <FormSelect
                id="trade"
                v-model="form.trade"
                label="Trade"
                icon="ti ti-briefcase"
                :options="trades"
                searchable
                :error="form.errors.trade"
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormSelect
                    id="state"
                    v-model="form.state"
                    label="State"
                    icon="ti ti-map-pin"
                    :options="states"
                    searchable
                    :error="form.errors.state"
                    @change="form.lga = ''"
                />
                <FormSelect
                    id="lga"
                    v-model="form.lga"
                    label="LGA"
                    icon="ti ti-building-community"
                    :options="lgas"
                    searchable
                    :disabled="!form.state"
                    :error="form.errors.lga"
                />
            </div>

            <FormTextarea
                id="office_address"
                v-model="form.office_address"
                label="Office / workshop address"
                icon="ti ti-home"
                :error="form.errors.office_address"
            />

            <FormTextInput
                id="whatsapp"
                v-model="form.whatsapp"
                type="tel"
                label="WhatsApp"
                icon="ti ti-brand-whatsapp"
                :error="form.errors.whatsapp"
            />

            <FormTextarea
                id="bio"
                v-model="form.bio"
                label="Short bio (optional)"
                icon="ti ti-quote"
                placeholder="A sentence clients will see on your page"
                :error="form.errors.bio"
            />

            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="text-sm text-ink/60">
                Your email is unverified.
                <Link
                    :href="route('verification.send')"
                    method="post"
                    as="button"
                    class="font-bold text-base"
                >
                    Resend verification
                </Link>
            </div>

            <FormButton
                type="submit"
                variant="primary"
                label="Save profile"
                :loading="form.processing"
                loading-label="Saving…"
            />
        </form>

        <div class="border-t border-ink/[0.06] pt-8">
            <h3 class="text-base font-bold tracking-tight text-ink">
                Public page URL
            </h3>
            <p class="mt-1 text-sm font-medium text-ink/50">
                You can change this
                <span class="font-semibold text-ink">{{ profile.slug_changes_remaining }}</span>
                more time{{ profile.slug_changes_remaining === 1 ? '' : 's' }}
                ({{ profile.max_slug_changes }} total). Old links always redirect.
            </p>

            <p
                v-if="profile.public_url"
                class="mt-3 rounded-xl bg-tint/50 px-3.5 py-2.5 text-xs font-semibold text-deep"
            >
                Current:
                <a :href="profile.public_url" class="font-bold underline-offset-2 hover:underline" target="_blank" rel="noopener">
                    {{ profile.public_url }}
                </a>
            </p>

            <form class="mt-4 space-y-3" @submit.prevent="submitSlug">
                <FormTextInput
                    id="slug"
                    v-model="slugForm.slug"
                    label="URL slug"
                    icon="ti ti-link"
                    hint="Lowercase letters, numbers, hyphens only."
                    :disabled="profile.slug_changes_remaining < 1"
                    :error="slugForm.errors.slug"
                />
                <FormButton
                    type="submit"
                    variant="secondary"
                    label="Update URL"
                    :disabled="profile.slug_changes_remaining < 1"
                    :loading="slugForm.processing"
                    loading-label="Updating…"
                />
            </form>
        </div>
    </section>
</template>

<script setup>
import FormButton from '@/Components/Form/FormButton.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    mustVerifyEmail: { type: Boolean, default: false },
    status: { type: String, default: '' },
    profile: { type: Object, required: true },
    locations: { type: Object, default: () => ({}) },
    trades: { type: Array, default: () => [] },
});

const user = usePage().props.auth.user;

const form = useForm({
    first_name: props.profile.first_name || '',
    last_name: props.profile.last_name || '',
    business_name: props.profile.business_name || '',
    email: props.profile.email || '',
    trade: props.profile.trade || '',
    state: props.profile.state || '',
    lga: props.profile.lga || '',
    office_address: props.profile.office_address || '',
    whatsapp: props.profile.whatsapp || '',
    bio: props.profile.bio || '',
});

const slugForm = useForm({
    slug: props.profile.slug || '',
});

const states = computed(() => Object.keys(props.locations));
const lgas = computed(() => (form.state ? props.locations[form.state] || [] : []));

const submitProfile = () => {
    form.patch(route('profile.update'), { preserveScroll: true });
};

const submitSlug = () => {
    slugForm.patch(route('profile.slug'), { preserveScroll: true });
};
</script>
