<template>
    <div class="space-y-5 sm:space-y-6">
        <!-- Basics -->
        <article class="presence-card overflow-hidden rounded-[1.35rem] bg-pale/40 ring-1 ring-ink/[0.06]">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-ink/[0.05] bg-white/70 px-4 py-4 sm:px-6">
                <div class="min-w-0">
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-base-action">
                        Basics
                    </p>
                    <h3 class="mt-1 font-editorial text-lg font-semibold tracking-tight text-ink">
                        Who you are
                    </h3>
                    <p class="mt-0.5 text-sm font-medium text-ink/45">
                        Name, business, trade, and the story clients read first.
                    </p>
                </div>
                <button
                    v-if="editing !== 'basics'"
                    type="button"
                    class="tap-target inline-flex shrink-0 items-center gap-2 rounded-2xl bg-base-action px-4 py-2.5 text-sm font-bold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.45)] transition-colors hover:bg-base-hover"
                    @click="startEditing('basics')"
                >
                    <i class="ti ti-pencil" aria-hidden="true" />
                    Edit
                </button>
            </div>

            <div class="px-4 py-5 sm:px-6 sm:py-6">
                <Transition
                    mode="out-in"
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-1"
                >
                    <div v-if="editing !== 'basics'" key="basics-view" class="space-y-5">
                        <div
                            class="flex flex-col gap-4 rounded-2xl bg-white p-4 ring-1 ring-ink/[0.05] sm:flex-row sm:items-center sm:p-5"
                        >
                            <div
                                class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-deep to-ink text-xl font-bold text-white shadow-sm"
                            >
                                <img
                                    v-if="profile.avatar_url"
                                    :src="profile.avatar_url"
                                    alt=""
                                    class="h-full w-full object-cover"
                                />
                                <span v-else>{{ initials }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-ink/40">
                                    Profile photo
                                </p>
                                <p class="mt-1 text-sm font-medium text-ink/55">
                                    {{
                                        profile.avatar_url
                                            ? 'Looking sharp — clients see this first.'
                                            : 'Add a clear photo so clients recognise you.'
                                    }}
                                </p>
                                <label
                                    class="tap-target mt-3 inline-flex cursor-pointer items-center gap-2 rounded-xl bg-base-action px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-base-hover"
                                >
                                    <i class="ti ti-camera" aria-hidden="true" />
                                    {{ profile.avatar_url ? 'Change photo' : 'Upload photo' }}
                                    <input
                                        type="file"
                                        accept="image/*"
                                        class="sr-only"
                                        :disabled="avatarForm.processing"
                                        @change="onAvatarPick"
                                    />
                                </label>
                            </div>
                        </div>

                        <dl class="grid gap-3 sm:grid-cols-2">
                            <div
                                v-for="row in basicsRows"
                                :key="row.label"
                                class="rounded-2xl bg-white px-4 py-3.5 ring-1 ring-ink/[0.05] sm:min-h-[5.25rem]"
                                :class="row.span ? 'sm:col-span-2' : ''"
                            >
                                <dt class="text-[11px] font-bold uppercase tracking-[0.1em] text-ink/40">
                                    {{ row.label }}
                                </dt>
                                <dd
                                    class="mt-1.5 text-sm font-semibold leading-relaxed text-ink"
                                    :class="row.value ? '' : 'font-medium text-ink/35'"
                                >
                                    <span class="whitespace-pre-wrap break-words">{{
                                        row.value || row.empty
                                    }}</span>
                                </dd>
                            </div>
                        </dl>

                        <div class="rounded-2xl bg-white px-4 py-4 ring-1 ring-ink/[0.05] sm:px-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-ink/40">
                                        Public page link
                                    </p>
                                    <a
                                        v-if="profile.public_url"
                                        :href="profile.public_url"
                                        target="_blank"
                                        rel="noopener"
                                        class="mt-1.5 block truncate text-sm font-bold text-deep underline-offset-2 hover:underline"
                                    >
                                        {{ profile.public_url.replace(/^https?:\/\//, '') }}
                                    </a>
                                    <p class="mt-1 text-xs font-medium text-ink/40">
                                        {{ profile.slug_changes_remaining }} change{{
                                            profile.slug_changes_remaining === 1 ? '' : 's'
                                        }}
                                        left
                                    </p>
                                </div>
                                <button
                                    v-if="!editingSlug && profile.slug_changes_remaining > 0"
                                    type="button"
                                    class="tap-target shrink-0 rounded-xl bg-tint px-3 py-2 text-xs font-bold text-base-action transition-colors hover:bg-base-action hover:text-white"
                                    @click="editingSlug = true"
                                >
                                    Change link
                                </button>
                            </div>

                            <Transition
                                enter-active-class="transition duration-300 ease-out"
                                enter-from-class="opacity-0 -translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition duration-200 ease-in"
                                leave-to-class="opacity-0"
                            >
                                <form
                                    v-if="editingSlug"
                                    class="mt-4 space-y-3 border-t border-ink/[0.06] pt-4"
                                    @submit.prevent="submitSlug"
                                >
                                    <FormTextInput
                                        id="slug"
                                        v-model="slugForm.slug"
                                        label="Link ending"
                                        icon="ti ti-link"
                                        hint="Letters, numbers and hyphens only."
                                        :error="slugForm.errors.slug"
                                    />
                                    <div class="flex flex-wrap gap-2">
                                        <FormButton
                                            type="submit"
                                            variant="primary"
                                            label="Save link"
                                            :loading="slugForm.processing"
                                            loading-label="Saving…"
                                        />
                                        <FormButton
                                            type="button"
                                            variant="ghost"
                                            label="Cancel"
                                            @click="cancelSlug"
                                        />
                                    </div>
                                </form>
                            </Transition>
                        </div>
                    </div>

                    <form
                        v-else
                        key="basics-edit"
                        class="space-y-4"
                        @submit.prevent="submitSection('basics')"
                    >
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <FormTextInput
                                id="first_name"
                                v-model="basicsForm.first_name"
                                label="First name"
                                icon="ti ti-user"
                                :error="basicsForm.errors.first_name"
                            />
                            <FormTextInput
                                id="last_name"
                                v-model="basicsForm.last_name"
                                label="Last name"
                                icon="ti ti-user"
                                :error="basicsForm.errors.last_name"
                            />
                        </div>

                        <FormTextInput
                            id="business_name"
                            v-model="basicsForm.business_name"
                            label="Business name"
                            icon="ti ti-building-store"
                            hint="Shown on your public page."
                            :error="basicsForm.errors.business_name"
                        />

                        <div>
                            <p class="mb-1.5 text-sm font-bold text-ink">Email</p>
                            <div
                                class="flex items-center gap-3 rounded-2xl bg-white px-4 py-3.5 ring-1 ring-ink/[0.06]"
                            >
                                <i class="ti ti-mail text-ink/35" aria-hidden="true" />
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-ink">
                                        {{ profile.email }}
                                    </p>
                                    <p class="mt-0.5 text-[11px] font-medium text-ink/40">
                                        Locked for security — contact support to change it.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <FormSelect
                            id="trade"
                            v-model="basicsForm.trade"
                            label="Trade / profession"
                            icon="ti ti-briefcase"
                            :options="trades"
                            searchable
                            :error="basicsForm.errors.trade"
                        />

                        <FormTextarea
                            id="bio"
                            v-model="basicsForm.bio"
                            label="Short bio (optional)"
                            icon="ti ti-quote"
                            placeholder="A sentence clients will see on your page"
                            :error="basicsForm.errors.bio"
                        />

                        <div
                            v-if="mustVerifyEmail && user.email_verified_at === null"
                            class="text-sm text-ink/60"
                        >
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

                        <div class="flex flex-wrap gap-2 pt-1">
                            <FormButton
                                type="submit"
                                variant="primary"
                                label="Save basics"
                                :loading="basicsForm.processing"
                                loading-label="Saving…"
                                icon-right="ti ti-check"
                            />
                            <FormButton
                                type="button"
                                variant="ghost"
                                label="Cancel"
                                @click="cancelEditing"
                            />
                        </div>
                    </form>
                </Transition>
            </div>
        </article>

        <!-- Expertise -->
        <article class="presence-card overflow-hidden rounded-[1.35rem] bg-pale/40 ring-1 ring-ink/[0.06]">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-ink/[0.05] bg-white/70 px-4 py-4 sm:px-6">
                <div class="min-w-0">
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-base-action">
                        Expertise
                    </p>
                    <h3 class="mt-1 font-editorial text-lg font-semibold tracking-tight text-ink">
                        Skills & credentials
                    </h3>
                    <p class="mt-0.5 text-sm font-medium text-ink/45">
                        What you’re great at, and the proof behind it.
                    </p>
                </div>
                <button
                    v-if="editing !== 'expertise'"
                    type="button"
                    class="tap-target inline-flex shrink-0 items-center gap-2 rounded-2xl bg-base-action px-4 py-2.5 text-sm font-bold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.45)] transition-colors hover:bg-base-hover"
                    @click="startEditing('expertise')"
                >
                    <i class="ti ti-pencil" aria-hidden="true" />
                    Edit
                </button>
            </div>

            <div class="px-4 py-5 sm:px-6 sm:py-6">
                <Transition
                    mode="out-in"
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-1"
                >
                    <div v-if="editing !== 'expertise'" key="expertise-view" class="space-y-4">
                        <div class="rounded-2xl bg-white px-4 py-4 ring-1 ring-ink/[0.05]">
                            <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-ink/40">
                                Skills
                            </p>
                            <div v-if="profile.skills?.length" class="mt-3 flex flex-wrap gap-2">
                                <span
                                    v-for="skill in profile.skills"
                                    :key="skill"
                                    class="inline-flex rounded-full bg-tint px-3 py-1.5 text-xs font-bold text-deep"
                                >
                                    {{ skill }}
                                </span>
                            </div>
                            <p v-else class="mt-2 text-sm font-medium text-ink/35">No skills added yet</p>
                        </div>

                        <div class="rounded-2xl bg-white px-4 py-4 ring-1 ring-ink/[0.05]">
                            <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-ink/40">
                                Credentials
                            </p>
                            <ul v-if="profile.credentials?.length" class="mt-3 space-y-2">
                                <li
                                    v-for="(cred, i) in profile.credentials"
                                    :key="i"
                                    class="rounded-xl bg-pale px-3.5 py-3 ring-1 ring-ink/[0.04]"
                                >
                                    <p class="text-sm font-bold text-ink">{{ cred.title }}</p>
                                    <p
                                        v-if="cred.issuer || cred.year"
                                        class="mt-0.5 text-xs font-medium text-ink/45"
                                    >
                                        <span v-if="cred.issuer">{{ cred.issuer }}</span>
                                        <span v-if="cred.issuer && cred.year"> · </span>
                                        <span v-if="cred.year">{{ cred.year }}</span>
                                    </p>
                                </li>
                            </ul>
                            <p v-else class="mt-2 text-sm font-medium text-ink/35">None listed</p>
                        </div>

                        <div class="rounded-2xl bg-white px-4 py-3.5 ring-1 ring-ink/[0.05]">
                            <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-ink/40">
                                Experience
                            </p>
                            <p
                                class="mt-1.5 text-sm font-semibold text-ink"
                                :class="yearsActiveLabel ? '' : 'font-medium text-ink/35'"
                            >
                                {{ yearsActiveLabel || 'Not set' }}
                            </p>
                        </div>
                    </div>

                    <form
                        v-else
                        key="expertise-edit"
                        class="space-y-5"
                        @submit.prevent="submitSection('expertise')"
                    >
                        <div class="relative">
                            <div class="mb-2 flex items-end justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-ink">Skills</p>
                                    <p class="mt-0.5 text-xs font-medium text-ink/45">
                                        Type to search, then tap a match. Up to 8.
                                    </p>
                                </div>
                                <span class="text-[11px] font-bold tabular-nums text-ink/40">
                                    {{ expertiseForm.skills.length }}/8
                                </span>
                            </div>

                            <div v-if="expertiseForm.skills.length" class="mb-3 flex flex-wrap gap-2">
                                <button
                                    v-for="skill in expertiseForm.skills"
                                    :key="skill"
                                    type="button"
                                    class="tap-target inline-flex items-center gap-1.5 rounded-full bg-base-action px-3 py-1.5 text-xs font-bold text-white transition hover:bg-base-hover"
                                    @click="removeSkill(skill)"
                                >
                                    {{ skill }}
                                    <i class="ti ti-x text-[13px] opacity-80" aria-hidden="true" />
                                </button>
                            </div>

                            <div class="relative">
                                <FormTextInput
                                    id="skill_search"
                                    v-model="skillQuery"
                                    label="Add a skill"
                                    placeholder="Start typing, e.g. wiring or tiling"
                                    icon="ti ti-search"
                                    autocomplete="off"
                                    :disabled="expertiseForm.skills.length >= 8"
                                    :error="expertiseForm.errors.skills || expertiseForm.errors['skills.0']"
                                    @focus="skillsOpen = true"
                                    @keydown.enter.prevent="pickFirstMatch"
                                    @keydown.escape="skillsOpen = false"
                                />

                                <ul
                                    v-if="skillsOpen && skillMatches.length"
                                    class="absolute z-20 mt-1.5 max-h-56 w-full overflow-auto rounded-2xl bg-white py-1.5 shadow-premium-hover ring-1 ring-ink/[0.08]"
                                    role="listbox"
                                >
                                    <li v-for="skill in skillMatches" :key="skill">
                                        <button
                                            type="button"
                                            class="flex w-full items-center gap-2 px-3.5 py-2.5 text-left text-sm font-semibold text-ink transition-colors hover:bg-tint"
                                            @mousedown.prevent="addSkill(skill)"
                                        >
                                            <i class="ti ti-plus text-base text-ink/35" aria-hidden="true" />
                                            {{ skill }}
                                        </button>
                                    </li>
                                </ul>

                                <p
                                    v-else-if="skillsOpen && skillQuery.trim() && !skillMatches.length"
                                    class="mt-2 text-xs font-medium text-ink/45"
                                >
                                    No match in the list.
                                    <button
                                        type="button"
                                        class="font-bold text-base-action hover:text-base-hover"
                                        :disabled="expertiseForm.skills.length >= 8"
                                        @click="addCustomSkill"
                                    >
                                        Add “{{ skillQuery.trim() }}” anyway
                                    </button>
                                </p>
                            </div>
                        </div>

                        <div class="border-t border-ink/[0.06] pt-5">
                            <CredentialsFieldset
                                v-model="expertiseForm.credentials"
                                :catalogue="credentialCatalogue"
                                :max="maxCredentials"
                                :errors="expertiseForm.errors"
                            />
                        </div>

                        <FormTextInput
                            id="experience_started_year"
                            v-model="expertiseForm.experience_started_year"
                            type="number"
                            label="Year you started this trade (optional)"
                            icon="ti ti-calendar"
                            :placeholder="`e.g. ${currentYear - 8}`"
                            hint="Shown as “X years active” on your page."
                            :error="expertiseForm.errors.experience_started_year"
                        />

                        <div class="flex flex-wrap gap-2 pt-1">
                            <FormButton
                                type="submit"
                                variant="primary"
                                label="Save expertise"
                                :loading="expertiseForm.processing"
                                loading-label="Saving…"
                                icon-right="ti ti-check"
                            />
                            <FormButton
                                type="button"
                                variant="ghost"
                                label="Cancel"
                                @click="cancelEditing"
                            />
                        </div>
                    </form>
                </Transition>
            </div>
        </article>

        <!-- Contact / location -->
        <article class="presence-card overflow-hidden rounded-[1.35rem] bg-pale/40 ring-1 ring-ink/[0.06]">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-ink/[0.05] bg-white/70 px-4 py-4 sm:px-6">
                <div class="min-w-0">
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-base-action">
                        Reach
                    </p>
                    <h3 class="mt-1 font-editorial text-lg font-semibold tracking-tight text-ink">
                        Location & WhatsApp
                    </h3>
                    <p class="mt-0.5 text-sm font-medium text-ink/45">
                        Where you work and how new clients message you.
                    </p>
                </div>
                <button
                    v-if="editing !== 'contact'"
                    type="button"
                    class="tap-target inline-flex shrink-0 items-center gap-2 rounded-2xl bg-base-action px-4 py-2.5 text-sm font-bold text-white shadow-[0_10px_24px_-10px_rgba(26,79,181,0.45)] transition-colors hover:bg-base-hover"
                    @click="startEditing('contact')"
                >
                    <i class="ti ti-pencil" aria-hidden="true" />
                    Edit
                </button>
            </div>

            <div class="px-4 py-5 sm:px-6 sm:py-6">
                <Transition
                    mode="out-in"
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-1"
                >
                    <div v-if="editing !== 'contact'" key="contact-view" class="space-y-3">
                        <dl class="grid gap-3 sm:grid-cols-2">
                            <div
                                v-for="row in contactRows"
                                :key="row.label"
                                class="rounded-2xl bg-white px-4 py-3.5 ring-1 ring-ink/[0.05]"
                                :class="row.span ? 'sm:col-span-2' : ''"
                            >
                                <dt class="text-[11px] font-bold uppercase tracking-[0.1em] text-ink/40">
                                    {{ row.label }}
                                </dt>
                                <dd class="mt-1.5 min-w-0 text-sm font-semibold text-ink">
                                    <template v-if="row.type === 'badges'">
                                        <div v-if="row.values?.length" class="flex flex-wrap gap-1.5">
                                            <span
                                                v-for="value in row.values"
                                                :key="value"
                                                class="inline-flex rounded-full bg-tint px-2.5 py-1 text-xs font-bold text-deep"
                                            >
                                                {{ value }}
                                            </span>
                                        </div>
                                        <span v-else class="font-medium text-ink/35">{{ row.empty }}</span>
                                    </template>
                                    <p
                                        v-else
                                        class="whitespace-pre-wrap break-words"
                                        :class="row.value ? '' : 'font-medium text-ink/35'"
                                    >
                                        {{ row.value || row.empty }}
                                    </p>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <form
                        v-else
                        key="contact-edit"
                        class="space-y-4"
                        @submit.prevent="submitSection('contact')"
                    >
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <FormSelect
                                id="state"
                                v-model="contactForm.state"
                                label="State"
                                icon="ti ti-map-pin"
                                :options="states"
                                searchable
                                :error="contactForm.errors.state"
                                @change="onStateChange"
                            />
                            <FormSelect
                                id="lga"
                                v-model="contactForm.lga"
                                label="LGA"
                                icon="ti ti-building-community"
                                :options="lgas"
                                searchable
                                :disabled="!contactForm.state"
                                :error="contactForm.errors.lga"
                            />
                        </div>

                        <div class="border-t border-ink/[0.06] pt-5">
                            <div class="mb-2 flex items-end justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-ink">Areas you cover</p>
                                    <p class="mt-0.5 text-xs font-medium text-ink/45">
                                        Helps clients see if you reach them.
                                    </p>
                                </div>
                                <span class="text-[11px] font-bold tabular-nums text-ink/40">
                                    {{ contactForm.coverage_areas.length }}/10
                                </span>
                            </div>

                            <div
                                v-if="contactForm.coverage_areas.length"
                                class="mb-3 flex flex-wrap gap-2"
                            >
                                <button
                                    v-for="area in contactForm.coverage_areas"
                                    :key="area"
                                    type="button"
                                    class="tap-target inline-flex items-center gap-1.5 rounded-full bg-base-action px-3 py-1.5 text-xs font-bold text-white transition hover:bg-base-hover"
                                    @click="removeArea(area)"
                                >
                                    {{ area }}
                                    <i class="ti ti-x text-[13px] opacity-80" aria-hidden="true" />
                                </button>
                            </div>

                            <div v-if="nearbySuggestions.length" class="mb-3 flex flex-wrap gap-2">
                                <button
                                    v-for="area in nearbySuggestions"
                                    :key="area"
                                    type="button"
                                    class="tap-target rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-ink/65 ring-1 ring-ink/[0.08] transition hover:bg-tint hover:text-deep disabled:cursor-not-allowed disabled:opacity-40"
                                    :disabled="contactForm.coverage_areas.length >= 10"
                                    @click="addArea(area)"
                                >
                                    + {{ area }}
                                </button>
                            </div>

                            <FormSelect
                                id="coverage_area_picker"
                                v-model="areaPicker"
                                label="Add an area outside your state"
                                icon="ti ti-map-2"
                                placeholder="Search any Nigerian LGA"
                                :options="allLgas"
                                searchable
                                :disabled="contactForm.coverage_areas.length >= 10"
                                :error="
                                    contactForm.errors.coverage_areas ||
                                    contactForm.errors['coverage_areas.0']
                                "
                            />

                            <div class="mt-4">
                                <FormTextInput
                                    id="coverage_note"
                                    v-model="contactForm.coverage_note"
                                    label="Coverage note (optional)"
                                    icon="ti ti-route"
                                    placeholder="e.g. Travels within 20km of Ikeja for a call-out fee"
                                    :error="contactForm.errors.coverage_note"
                                />
                            </div>
                        </div>

                        <FormTextarea
                            id="office_address"
                            v-model="contactForm.office_address"
                            label="Office / workshop address"
                            icon="ti ti-home"
                            :error="contactForm.errors.office_address"
                        />

                        <FormTextInput
                            id="whatsapp"
                            v-model="contactForm.whatsapp"
                            type="tel"
                            label="WhatsApp number"
                            icon="ti ti-brand-whatsapp"
                            placeholder="0803…"
                            hint="Clients reach you here. Use a Nigerian number."
                            :error="contactForm.errors.whatsapp"
                        />

                        <div class="flex flex-wrap gap-2 pt-1">
                            <FormButton
                                type="submit"
                                variant="primary"
                                label="Save location"
                                :loading="contactForm.processing"
                                loading-label="Saving…"
                                icon-right="ti ti-check"
                            />
                            <FormButton
                                type="button"
                                variant="ghost"
                                label="Cancel"
                                @click="cancelEditing"
                            />
                        </div>
                    </form>
                </Transition>
            </div>
        </article>
    </div>
</template>

<script setup>
import FormButton from '@/Components/Form/FormButton.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import FormTextarea from '@/Components/Form/FormTextarea.vue';
import FormTextInput from '@/Components/Form/FormTextInput.vue';
import CredentialsFieldset from '@/Components/Profile/CredentialsFieldset.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    mustVerifyEmail: { type: Boolean, default: false },
    status: { type: String, default: '' },
    profile: { type: Object, required: true },
    locations: { type: Object, default: () => ({}) },
    trades: { type: Array, default: () => [] },
    skillSuggestions: { type: Array, default: () => [] },
    credentialCatalogue: { type: Array, default: () => [] },
    maxCredentials: { type: Number, default: 6 },
    showHeader: { type: Boolean, default: true },
});

const user = usePage().props.auth.user;
const editing = ref(null);
const editingSlug = ref(false);
const skillQuery = ref('');
const skillsOpen = ref(false);
const areaPicker = ref('');
const currentYear = new Date().getFullYear();

const initials = computed(() => {
    const first = String(props.profile.first_name || '').trim();
    const last = String(props.profile.last_name || '').trim();
    const business = String(props.profile.business_name || '').trim();
    if (first || last) {
        return `${first.charAt(0)}${last.charAt(0)}`.toUpperCase() || 'I';
    }
    return business.slice(0, 2).toUpperCase() || 'I';
});

const avatarForm = useForm({
    avatar: null,
});

const onAvatarPick = (event) => {
    const file = event.target?.files?.[0];
    if (!file) return;
    avatarForm.avatar = file;
    avatarForm.post(route('profile.avatar'), {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            avatarForm.reset('avatar');
            if (event.target) event.target.value = '';
        },
    });
};

const mapCredentials = () =>
    Array.isArray(props.profile.credentials)
        ? props.profile.credentials.map((c) => ({
              title: c.title || '',
              issuer: c.issuer || '',
              reference: c.reference || '',
              year: c.year ?? '',
          }))
        : [];

const basicsForm = useForm({
    section: 'basics',
    first_name: props.profile.first_name || '',
    last_name: props.profile.last_name || '',
    business_name: props.profile.business_name || '',
    trade: props.profile.trade || '',
    bio: props.profile.bio || '',
});

const expertiseForm = useForm({
    section: 'expertise',
    skills: Array.isArray(props.profile.skills) ? [...props.profile.skills] : [],
    credentials: mapCredentials(),
    experience_started_year: props.profile.experience_started_year ?? '',
});

const contactForm = useForm({
    section: 'contact',
    state: props.profile.state || '',
    lga: props.profile.lga || '',
    coverage_areas: Array.isArray(props.profile.coverage_areas)
        ? [...props.profile.coverage_areas]
        : [],
    coverage_note: props.profile.coverage_note || '',
    office_address: props.profile.office_address || '',
    whatsapp: props.profile.whatsapp || '',
});

const slugForm = useForm({
    slug: props.profile.slug || '',
});

const yearsActive = computed(() => {
    const year = Number(props.profile.experience_started_year);
    if (!year || year > currentYear) return null;
    return Math.max(0, currentYear - year);
});

const yearsActiveLabel = computed(() => {
    if (yearsActive.value == null) return '';
    return `Started ${props.profile.experience_started_year} · ${yearsActive.value} year${
        yearsActive.value === 1 ? '' : 's'
    } active`;
});

const basicsRows = computed(() => [
    {
        label: 'Name',
        value: [props.profile.first_name, props.profile.last_name].filter(Boolean).join(' '),
        empty: 'Not set',
    },
    {
        label: 'Business',
        value: props.profile.business_name,
        empty: 'Not set',
    },
    {
        label: 'Email',
        value: props.profile.email,
        empty: 'Not set',
    },
    {
        label: 'Trade',
        value: props.profile.trade,
        empty: 'Not set',
    },
    {
        label: 'Bio',
        value: props.profile.bio,
        empty: 'No bio yet',
        span: true,
    },
]);

const contactRows = computed(() => [
    {
        label: 'Location',
        value: [props.profile.lga, props.profile.state].filter(Boolean).join(', '),
        empty: 'Not set',
    },
    {
        label: 'WhatsApp',
        value: props.profile.whatsapp,
        empty: 'Not set',
    },
    {
        label: 'Coverage',
        type: 'badges',
        values: props.profile.coverage_areas || [],
        empty: 'No coverage areas yet',
        span: true,
    },
    {
        label: 'Coverage note',
        value: props.profile.coverage_note,
        empty: 'No coverage note',
        span: true,
    },
    {
        label: 'Address',
        value: props.profile.office_address,
        empty: 'Not set',
        span: true,
    },
]);

const states = computed(() => Object.keys(props.locations));
const lgas = computed(() => (contactForm.state ? props.locations[contactForm.state] || [] : []));
const allLgas = computed(() =>
    [...new Set(Object.values(props.locations).flat())].sort((a, b) => a.localeCompare(b)),
);

const nearbySuggestions = computed(() => {
    const chosen = new Set(contactForm.coverage_areas);
    return lgas.value.filter((lga) => !chosen.has(lga)).slice(0, 12);
});

const skillMatches = computed(() => {
    const q = skillQuery.value.trim().toLowerCase();
    if (!q) return [];
    const selected = new Set(expertiseForm.skills.map((s) => s.toLowerCase()));
    return props.skillSuggestions
        .filter((s) => !selected.has(String(s).toLowerCase()))
        .filter((s) => String(s).toLowerCase().includes(q))
        .slice(0, 8);
});

const hydrateBasics = () => {
    basicsForm.first_name = props.profile.first_name || '';
    basicsForm.last_name = props.profile.last_name || '';
    basicsForm.business_name = props.profile.business_name || '';
    basicsForm.trade = props.profile.trade || '';
    basicsForm.bio = props.profile.bio || '';
    basicsForm.clearErrors();
};

const hydrateExpertise = () => {
    expertiseForm.skills = Array.isArray(props.profile.skills) ? [...props.profile.skills] : [];
    expertiseForm.credentials = mapCredentials();
    expertiseForm.experience_started_year = props.profile.experience_started_year ?? '';
    expertiseForm.clearErrors();
};

const hydrateContact = () => {
    contactForm.state = props.profile.state || '';
    contactForm.lga = props.profile.lga || '';
    contactForm.coverage_areas = Array.isArray(props.profile.coverage_areas)
        ? [...props.profile.coverage_areas]
        : [];
    contactForm.coverage_note = props.profile.coverage_note || '';
    contactForm.office_address = props.profile.office_address || '';
    contactForm.whatsapp = props.profile.whatsapp || '';
    contactForm.clearErrors();
};

const startEditing = (section) => {
    editingSlug.value = false;
    if (section === 'basics') hydrateBasics();
    if (section === 'expertise') {
        hydrateExpertise();
        skillQuery.value = '';
    }
    if (section === 'contact') hydrateContact();
    editing.value = section;
};

const cancelEditing = () => {
    if (editing.value === 'basics') hydrateBasics();
    if (editing.value === 'expertise') {
        hydrateExpertise();
        skillQuery.value = '';
        skillsOpen.value = false;
    }
    if (editing.value === 'contact') hydrateContact();
    editing.value = null;
};

const forms = {
    basics: basicsForm,
    expertise: expertiseForm,
    contact: contactForm,
};

const submitSection = (section) => {
    forms[section].patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = null;
            skillQuery.value = '';
        },
    });
};

const addArea = (area) => {
    const value = String(area || '').trim();
    if (
        !value ||
        contactForm.coverage_areas.length >= 10 ||
        contactForm.coverage_areas.includes(value)
    ) {
        return;
    }
    contactForm.coverage_areas = [...contactForm.coverage_areas, value];
};

const removeArea = (area) => {
    contactForm.coverage_areas = contactForm.coverage_areas.filter((a) => a !== area);
};

const onStateChange = () => {
    contactForm.lga = '';
};

watch(areaPicker, (value) => {
    if (!value) return;
    addArea(value);
    areaPicker.value = '';
});

watch(skillQuery, () => {
    skillsOpen.value = true;
});

const addSkill = (skill) => {
    const value = String(skill || '').trim();
    if (!value || expertiseForm.skills.length >= 8) return;
    if (expertiseForm.skills.some((s) => s.toLowerCase() === value.toLowerCase())) return;
    expertiseForm.skills = [...expertiseForm.skills, value.slice(0, 40)];
    skillQuery.value = '';
    skillsOpen.value = false;
};

const removeSkill = (skill) => {
    expertiseForm.skills = expertiseForm.skills.filter((s) => s !== skill);
};

const addCustomSkill = () => addSkill(skillQuery.value);

const pickFirstMatch = () => {
    if (skillMatches.value.length) {
        addSkill(skillMatches.value[0]);
        return;
    }
    if (skillQuery.value.trim()) addCustomSkill();
};

const submitSlug = () => {
    slugForm.patch(route('profile.slug'), {
        preserveScroll: true,
        onSuccess: () => {
            editingSlug.value = false;
        },
    });
};

const cancelSlug = () => {
    slugForm.slug = props.profile.slug || '';
    slugForm.clearErrors();
    editingSlug.value = false;
};

const onDocClick = (event) => {
    if (!event.target.closest?.('#skill_search') && !event.target.closest?.('[role="listbox"]')) {
        skillsOpen.value = false;
    }
};

onMounted(() => document.addEventListener('click', onDocClick));
onUnmounted(() => document.removeEventListener('click', onDocClick));
</script>
