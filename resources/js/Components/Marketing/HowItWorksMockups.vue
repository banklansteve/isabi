<template>
    <!-- 1 · Sign up (Register — mobile form panel) -->
    <div
        v-if="variant === 'signup'"
        class="flex h-full flex-col bg-pale"
    >
        <div class="flex items-center justify-between px-3.5 pb-1 pt-9">
            <span class="font-display text-[13px] font-extrabold tracking-tight text-ink">Kraftrack</span>
            <span class="text-[9px] font-semibold text-ink/45">Sign in</span>
        </div>
        <div class="flex-1 overflow-hidden px-3.5 pb-3 pt-2">
            <div class="flex items-center justify-between gap-2">
                <p class="text-[8px] font-semibold uppercase tracking-[0.16em] text-base">
                    Step 4 of 4
                </p>
                <p class="text-[8px] font-semibold text-ink/40">100%</p>
            </div>
            <div class="mt-1.5 h-1 overflow-hidden rounded-full bg-ink/8">
                <div class="h-full w-full rounded-full bg-gradient-to-r from-base to-coral" />
            </div>
            <div class="mt-2 flex gap-1">
                <span
                    v-for="n in 4"
                    :key="n"
                    class="h-1 flex-1 rounded-full bg-base"
                />
            </div>

            <p class="mt-3.5 text-center text-[8px] font-bold uppercase tracking-[0.14em] text-ink/40">
                Almost there
            </p>
            <p class="mt-1 text-center font-display text-[15px] font-extrabold leading-tight tracking-tight text-ink">
                Contact &amp; password
            </p>
            <p class="mt-1 text-center text-[9px] font-semibold leading-snug text-ink/50">
                WhatsApp for review links. No card required.
            </p>

            <div class="mt-3 space-y-2">
                <div class="rounded-xl bg-white px-2.5 py-2 shadow-sm ring-1 ring-ink/[0.06]">
                    <div class="flex items-center gap-2">
                        <i class="ti ti-briefcase text-[11px] text-ink/35" />
                        <div class="min-w-0">
                            <p class="text-[7px] font-bold uppercase tracking-wide text-ink/35">Trade</p>
                            <p class="text-[10px] font-semibold text-ink">Electrician</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl bg-white px-2.5 py-2 shadow-sm ring-1 ring-ink/[0.06]">
                    <div class="flex items-center gap-2">
                        <i class="ti ti-mail text-[11px] text-ink/35" />
                        <div class="min-w-0">
                            <p class="text-[7px] font-bold uppercase tracking-wide text-ink/35">Email</p>
                            <p class="truncate text-[10px] font-semibold text-ink">segun@email.com</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl bg-white px-2.5 py-2 ring-2 ring-base/25 shadow-sm">
                    <div class="flex items-center gap-2">
                        <i class="ti ti-brand-whatsapp text-[11px] text-[#25D366]" />
                        <div class="min-w-0 flex-1">
                            <p class="text-[7px] font-bold uppercase tracking-wide text-ink/35">
                                WhatsApp number
                            </p>
                            <p class="text-[10px] font-semibold text-ink">0803 000 0000</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl bg-white px-2.5 py-2 shadow-sm ring-1 ring-ink/[0.06]">
                    <div class="flex items-center gap-2">
                        <i class="ti ti-lock text-[11px] text-ink/35" />
                        <div class="min-w-0 flex-1">
                            <p class="text-[7px] font-bold uppercase tracking-wide text-ink/35">Password</p>
                            <p class="text-[10px] font-semibold tracking-widest text-ink">••••••••</p>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="mt-3 rounded-xl bg-coral py-2.5 text-center text-[10px] font-bold text-white shadow-[0_8px_18px_-10px_rgba(255,106,61,0.7)]"
            >
                Create account
            </div>
            <p class="mt-2 text-center text-[7px] font-medium text-ink/35">
                Free to start · No card required
            </p>
        </div>
    </div>

    <!-- 2 · Set up your page (Account / Basics) -->
    <div
        v-else-if="variant === 'setup'"
        class="flex h-full flex-col bg-pale"
    >
        <div
            class="relative overflow-hidden bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-3 pb-4 pt-9 text-white"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.14),transparent_55%),radial-gradient(45%_55%_at_100%_100%,rgba(255,106,61,0.14),transparent_50%)]"
            />
            <div class="relative flex items-center gap-2.5">
                <div
                    class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-2xl bg-white text-[12px] font-bold text-deep shadow-sm"
                >
                    SA
                </div>
                <div class="min-w-0">
                    <p
                        class="inline-flex items-center gap-1 text-[7px] font-semibold uppercase tracking-[0.14em] text-white/55"
                    >
                        <span class="h-1 w-1 rounded-full bg-coral" />
                        Account
                    </p>
                    <p class="truncate font-editorial text-[14px] font-semibold leading-tight">
                        Segun Adebayo
                    </p>
                </div>
            </div>
        </div>

        <div class="flex gap-1 overflow-hidden px-2.5 py-2">
            <span
                class="rounded-full bg-base-action px-2.5 py-1 text-[8px] font-bold text-white shadow-sm"
            >
                Basics
            </span>
            <span
                class="rounded-full bg-white px-2.5 py-1 text-[8px] font-bold text-ink/45 ring-1 ring-ink/[0.06]"
            >
                Expertise
            </span>
            <span
                class="rounded-full bg-white px-2.5 py-1 text-[8px] font-bold text-ink/45 ring-1 ring-ink/[0.06]"
            >
                Reach
            </span>
        </div>

        <div class="mx-2.5 overflow-hidden rounded-[1.1rem] bg-white shadow-premium ring-1 ring-ink/[0.06]">
            <div
                class="relative overflow-hidden px-3 py-2.5 text-white"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427]"
                />
                <div class="relative">
                    <p class="font-editorial text-[12px] font-semibold">Your public presence</p>
                    <p class="mt-0.5 text-[8px] font-medium text-white/65">
                        Basics, expertise, and where clients reach you.
                    </p>
                </div>
            </div>
            <div class="space-y-2 p-2.5">
                <div class="rounded-xl bg-pale/80 px-2.5 py-2 ring-1 ring-ink/[0.05]">
                    <p class="text-[7px] font-bold uppercase tracking-wide text-base-action">Basics</p>
                    <p class="mt-0.5 font-editorial text-[11px] font-semibold text-ink">Who you are</p>
                </div>
                <div class="flex items-center gap-2.5 rounded-xl bg-white p-2 ring-1 ring-ink/[0.05]">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-deep to-ink text-[10px] font-bold text-white"
                    >
                        SA
                    </div>
                    <div class="min-w-0">
                        <p class="text-[7px] font-bold uppercase text-ink/40">Profile photo</p>
                        <p class="text-[9px] font-medium text-ink/55">Add a clear photo</p>
                        <span
                            class="mt-1 inline-flex items-center gap-1 rounded-lg bg-base-action px-2 py-1 text-[8px] font-bold text-white"
                        >
                            <i class="ti ti-camera text-[9px]" /> Upload photo
                        </span>
                    </div>
                </div>
                <div class="rounded-xl bg-white px-2.5 py-2 ring-1 ring-ink/[0.05]">
                    <p class="text-[7px] font-bold uppercase text-ink/40">Service area</p>
                    <p class="mt-0.5 text-[10px] font-semibold text-ink">Lekki · Eti-Osa, Lagos</p>
                </div>
                <div class="rounded-xl bg-white px-2.5 py-2 ring-1 ring-ink/[0.05]">
                    <p class="text-[7px] font-bold uppercase text-ink/40">Short bio</p>
                    <p class="mt-0.5 text-[9px] font-medium leading-snug text-ink/65">
                        Licensed electrician. Clean work, clear pricing.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- 3 · Log a finished job -->
    <div
        v-else-if="variant === 'log'"
        class="flex h-full flex-col bg-pale"
    >
        <div
            class="relative overflow-hidden bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-3 pb-3.5 pt-9 text-white"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_10%_0%,rgba(255,255,255,0.14),transparent_55%)]"
            />
            <p class="relative text-[8px] font-semibold text-white/55">
                <i class="ti ti-arrow-left text-[9px]" /> Work log
            </p>
            <p
                class="relative mt-2 inline-flex items-center gap-1 text-[7px] font-semibold uppercase tracking-[0.14em] text-white/55"
            >
                <span class="h-1 w-1 rounded-full bg-coral" />
                New record
            </p>
            <p class="relative mt-1 font-editorial text-[16px] font-semibold leading-tight">
                Log a job
            </p>
            <p class="relative mt-1 text-[8px] font-medium leading-snug text-white/60">
                Under 30 seconds. Photo optional.
            </p>
        </div>

        <div class="mx-2.5 mt-2 rounded-xl bg-white p-2 shadow-premium ring-1 ring-ink/[0.06]">
            <div class="flex gap-1">
                <span class="h-1 flex-1 rounded-full bg-base" />
                <span class="h-1 flex-1 rounded-full bg-ink/10" />
                <span class="h-1 flex-1 rounded-full bg-ink/10" />
            </div>
            <p class="mt-1.5 text-center text-[8px] font-bold text-ink/45">Essentials</p>
        </div>

        <div class="mx-2.5 mt-2 flex-1 overflow-hidden rounded-[1.15rem] bg-white shadow-premium ring-1 ring-ink/[0.06]">
            <div
                class="flex items-start justify-between gap-2 border-b border-ink/[0.05] bg-gradient-to-r from-pale/90 to-white px-3 py-2.5"
            >
                <div>
                    <p class="font-editorial text-[12px] font-semibold text-ink">The essentials</p>
                    <p class="text-[8px] font-medium text-ink/45">Required to save a job</p>
                </div>
                <span
                    class="rounded-full bg-ink px-2 py-0.5 text-[7px] font-bold uppercase tracking-wide text-white"
                >
                    Required
                </span>
            </div>
            <div class="space-y-2.5 p-3">
                <div class="rounded-xl bg-pale px-2.5 py-2 ring-1 ring-ink/[0.05]">
                    <div class="flex items-center gap-1.5">
                        <i class="ti ti-tool text-[10px] text-ink/35" />
                        <p class="text-[7px] font-bold uppercase text-ink/35">What was done</p>
                    </div>
                    <p class="mt-1 text-[11px] font-semibold text-ink">Kitchen rewiring</p>
                </div>
                <div class="rounded-xl bg-pale px-2.5 py-2 ring-1 ring-ink/[0.05]">
                    <div class="flex items-center gap-1.5">
                        <i class="ti ti-calendar text-[10px] text-ink/35" />
                        <p class="text-[7px] font-bold uppercase text-ink/35">Date</p>
                    </div>
                    <p class="mt-1 text-[11px] font-semibold text-ink">12 Jul 2026</p>
                </div>
                <div
                    class="flex items-center gap-2 rounded-xl border border-dashed border-ink/15 bg-pale/50 px-2.5 py-2.5"
                >
                    <i class="ti ti-photo text-[14px] text-ink/30" />
                    <div>
                        <p class="text-[9px] font-bold text-ink/55">Add photo</p>
                        <p class="text-[7px] font-medium text-ink/35">Optional</p>
                    </div>
                </div>
                <div
                    class="rounded-xl bg-base-action py-2.5 text-center text-[10px] font-bold text-white shadow-[0_8px_18px_-10px_rgba(26,79,181,0.55)]"
                >
                    Save job
                </div>
            </div>
        </div>
    </div>

    <!-- 4 · Send a review request (job detail + WhatsApp sheet) -->
    <div
        v-else-if="variant === 'send'"
        class="relative flex h-full flex-col bg-pale"
    >
        <div
            class="relative overflow-hidden bg-gradient-to-br from-[#1A4FB5] via-[#123B72] to-[#071427] px-3 pb-4 pt-9 text-white"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_80%_at_12%_0%,rgba(255,255,255,0.14),transparent_55%)]"
            />
            <div class="relative flex items-center justify-between">
                <p class="text-[8px] font-semibold text-white/55">
                    <i class="ti ti-arrow-left text-[9px]" /> Work log
                </p>
                <span
                    class="inline-flex items-center gap-1 rounded-full bg-white/10 px-2 py-0.5 text-[7px] font-bold text-white/70 ring-1 ring-white/15"
                >
                    <i class="ti ti-check text-[9px]" /> Logged
                </span>
            </div>
            <p class="relative mt-3 text-[7px] font-semibold uppercase tracking-[0.14em] text-white/45">
                Electrical
            </p>
            <p class="relative mt-1 font-editorial text-[15px] font-semibold leading-tight">
                Kitchen rewiring
            </p>
            <p class="relative mt-2 flex items-center gap-1 text-[9px] font-medium text-white/60">
                <i class="ti ti-calendar-event text-[10px] text-white/40" />
                12 Jul 2026
            </p>
            <div
                class="relative mt-3 inline-flex items-center gap-1.5 rounded-xl bg-coral px-3 py-2 text-[9px] font-bold text-white shadow-[0_8px_18px_-10px_rgba(255,106,61,0.6)]"
            >
                <i class="ti ti-brand-whatsapp text-[12px]" />
                Send review link
            </div>
        </div>

        <div class="mx-2.5 mt-2 rounded-xl bg-tint/60 px-2.5 py-2 ring-1 ring-base/10">
            <p class="text-[8px] font-medium text-ink/65">
                Free links this month:
                <span class="font-bold text-ink">4 of 5</span>
                left.
            </p>
        </div>

        <!-- Sheet overlay -->
        <div class="absolute inset-x-0 bottom-0 z-10 rounded-t-[1.25rem] bg-white px-3 pb-4 pt-3 shadow-[0_-12px_40px_-16px_rgba(7,20,39,0.35)] ring-1 ring-ink/[0.06]">
            <div class="mx-auto mb-2.5 h-1 w-10 rounded-full bg-ink/10" />
            <div class="flex items-start gap-2.5">
                <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#25D366]/15 text-[#25D366]"
                >
                    <i class="ti ti-brand-whatsapp text-[16px]" />
                </span>
                <div class="min-w-0">
                    <p class="text-[12px] font-bold text-ink">Send review link</p>
                    <p class="mt-0.5 text-[8px] font-medium leading-snug text-ink/45">
                        Opens WhatsApp with a private link for this job.
                    </p>
                </div>
            </div>
            <div
                class="mt-2.5 max-h-14 overflow-hidden rounded-xl bg-pale/80 px-2.5 py-2 text-[8px] font-medium leading-relaxed text-ink/55"
            >
                Hi — please rate the kitchen rewiring job on kraftrack:
                kraftrack.com/r/Kx8m2…
            </div>
            <div
                class="mt-2.5 flex w-full items-center justify-center gap-1.5 rounded-xl bg-[#25D366] py-2.5 text-[10px] font-bold text-white"
            >
                <i class="ti ti-brand-whatsapp text-[13px]" />
                Open WhatsApp
            </div>
            <div
                class="mt-1.5 flex w-full items-center justify-center gap-1.5 rounded-xl bg-pale py-2 text-[10px] font-bold text-ink ring-1 ring-ink/[0.06]"
            >
                <i class="ti ti-copy text-[12px]" />
                Copy review link
            </div>
        </div>
    </div>

    <!-- 5 · Client leaves a review -->
    <div
        v-else-if="variant === 'review'"
        class="flex h-full flex-col bg-pale"
    >
        <div class="relative overflow-hidden bg-ink px-3 pb-5 pt-9 text-white">
            <div
                class="pointer-events-none absolute -left-8 -top-8 h-28 w-28 rounded-full bg-base/30 blur-2xl"
            />
            <div
                class="pointer-events-none absolute -right-6 top-4 h-24 w-24 rounded-full bg-coral/20 blur-2xl"
            />
            <div class="relative flex items-center justify-between">
                <span class="font-display text-[12px] font-extrabold">Kraftrack</span>
                <span
                    class="inline-flex items-center gap-1 rounded-full bg-white/10 px-2 py-0.5 text-[7px] font-bold uppercase tracking-wider text-white/70 ring-1 ring-white/15"
                >
                    <span class="h-1 w-1 rounded-full bg-[#F5A524]" />
                    Private invite
                </span>
            </div>
            <div class="relative mt-3.5 flex items-center gap-2.5">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-[0.9rem] bg-gradient-to-br from-base to-deep font-display text-[12px] font-extrabold ring-1 ring-white/20"
                >
                    SA
                </div>
                <div class="min-w-0">
                    <p class="text-[7px] font-bold uppercase tracking-wider text-white/45">
                        You’re reviewing
                    </p>
                    <p class="truncate font-display text-[14px] font-extrabold leading-tight">
                        Segun Adebayo
                    </p>
                    <p class="text-[9px] font-medium text-white/50">Electrician</p>
                </div>
            </div>
            <div
                class="relative mt-3 rounded-[1rem] bg-white/[0.07] p-2.5 ring-1 ring-white/[0.12]"
            >
                <p class="text-[7px] font-bold uppercase tracking-wider text-white/40">The job</p>
                <p class="mt-1 text-[11px] font-semibold leading-snug text-white/90">
                    Kitchen rewiring
                </p>
                <div class="mt-1.5 flex flex-wrap gap-1">
                    <span class="rounded-full bg-white/12 px-2 py-0.5 text-[7px] font-bold text-white/80">
                        Electrical
                    </span>
                    <span
                        class="inline-flex items-center gap-0.5 rounded-full bg-white/8 px-2 py-0.5 text-[7px] font-semibold text-white/60"
                    >
                        <i class="ti ti-calendar text-[8px]" />
                        12 Jul 2026
                    </span>
                </div>
            </div>
        </div>

        <div
            class="-mt-3 flex-1 overflow-hidden rounded-t-[1.35rem] bg-white shadow-premium ring-1 ring-ink/[0.05]"
        >
            <div class="px-3 py-3.5">
                <div class="flex items-center justify-center gap-2">
                    <span
                        class="flex h-5 w-5 items-center justify-center rounded-full bg-tint text-[8px] font-extrabold text-deep"
                    >
                        1
                    </span>
                    <p class="font-display text-[12px] font-extrabold tracking-tight text-ink">
                        How was the work?
                    </p>
                </div>
                <div class="mt-2.5 flex justify-center gap-1 text-coral">
                    <i
                        v-for="n in 5"
                        :key="n"
                        class="ti ti-star-filled text-[18px]"
                    />
                </div>
                <p class="mt-1 text-center text-[7px] font-medium text-ink/40">
                    Tap a star — left half for a half rating
                </p>
            </div>
            <div class="mx-3 h-px bg-ink/[0.06]" />
            <div class="px-3 py-3">
                <div class="flex items-start gap-2">
                    <span
                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-tint text-[8px] font-extrabold text-deep"
                    >
                        2
                    </span>
                    <p class="font-display text-[11px] font-extrabold leading-snug text-ink">
                        Would you recommend Segun?
                    </p>
                </div>
                <div class="mt-2 grid grid-cols-2 gap-1.5">
                    <div
                        class="relative flex items-center justify-center gap-1 rounded-xl bg-emerald-500/10 py-2.5 text-[9px] font-extrabold text-emerald-700 ring-1 ring-emerald-500/25"
                    >
                        <i class="ti ti-thumb-up text-[12px]" />
                        Yes
                        <i class="ti ti-check absolute right-1.5 top-1 text-[9px]" />
                    </div>
                    <div
                        class="flex items-center justify-center gap-1 rounded-xl bg-pale py-2.5 text-[9px] font-extrabold text-ink/45 ring-1 ring-ink/[0.07]"
                    >
                        <i class="ti ti-thumb-down text-[12px]" />
                        No
                    </div>
                </div>
            </div>
            <div class="mx-3 h-px bg-ink/[0.06]" />
            <div class="px-3 py-3">
                <div class="flex items-start gap-2">
                    <span
                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-tint text-[8px] font-extrabold text-deep"
                    >
                        3
                    </span>
                    <div>
                        <p class="font-display text-[11px] font-extrabold text-ink">Tell them why</p>
                        <p class="text-[7px] font-medium text-ink/40">A sentence or two is plenty.</p>
                    </div>
                </div>
                <div class="mt-2 rounded-xl bg-pale px-2.5 py-2 ring-1 ring-ink/[0.05]">
                    <p class="text-[9px] font-medium leading-snug text-ink/65">
                        Showed up on time and explained everything clearly.
                    </p>
                </div>
                <div
                    class="mt-2.5 rounded-xl bg-coral py-2.5 text-center text-[10px] font-bold text-white"
                >
                    Submit review
                </div>
            </div>
        </div>
    </div>

    <!-- 6 · Review lands on the public page -->
    <div
        v-else
        class="flex h-full flex-col bg-pale"
    >
        <div class="relative overflow-hidden bg-[#071427] px-3 pb-5 pt-9 text-white">
            <div
                class="pointer-events-none absolute inset-x-0 bottom-0 top-8 bg-[radial-gradient(ellipse_80%_60%_at_15%_30%,rgba(47,111,237,0.26),transparent_58%)]"
            />
            <div class="relative flex flex-col items-center text-center">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-deep to-ink font-display text-[14px] font-extrabold ring-2 ring-white/20"
                >
                    SA
                </div>
                <p class="mt-2 text-[7px] font-bold uppercase tracking-[0.18em] text-coral/90">
                    Electrician
                </p>
                <p class="mt-1 font-editorial text-[14px] font-semibold leading-tight">
                    Segun Adebayo
                </p>
                <p class="mt-1 inline-flex items-center gap-1 text-[8px] font-medium text-white/55">
                    <i class="ti ti-map-pin text-[9px] text-coral" />
                    Lekki, Lagos
                </p>
            </div>
        </div>

        <div
            class="-mt-2.5 flex-1 overflow-hidden rounded-t-xl bg-pale px-2 pt-3"
        >
            <p class="text-[7px] font-bold uppercase tracking-[0.16em] text-coral-deep">Portfolio</p>
            <p class="mt-0.5 font-editorial text-[13px] font-semibold tracking-tight text-ink">
                Recent work
            </p>

            <article
                class="mt-2 overflow-hidden rounded-xl bg-white shadow-premium ring-1 ring-ink/[0.05]"
            >
                <div class="px-2.5 pt-2.5">
                    <div class="flex items-center gap-2">
                        <time class="text-[8px] font-bold tabular-nums text-ink/40">12 Jul 2026</time>
                        <span
                            class="ms-auto inline-flex items-center gap-1 text-[8px] font-bold text-ink/35"
                        >
                            <i class="ti ti-share-2 text-[9px]" /> Share
                        </span>
                    </div>
                    <h3 class="mt-2 font-editorial text-[13px] font-semibold leading-snug text-ink">
                        Kitchen rewiring
                    </h3>
                    <p class="mt-1 inline-flex items-center gap-1 text-[8px] font-medium text-ink/45">
                        <i class="ti ti-map-pin text-[9px] text-base-action" />
                        Lekki Phase 1
                    </p>
                </div>
                <figure class="m-2.5 rounded-xl bg-pale p-2.5 ring-1 ring-ink/[0.05]">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <div class="flex gap-0.5 text-[#F5A524]">
                            <i
                                v-for="n in 5"
                                :key="n"
                                class="ti ti-star-filled text-[10px]"
                            />
                        </div>
                        <span
                            class="inline-flex items-center gap-0.5 rounded-full bg-emerald-500/10 px-1.5 py-0.5 text-[6px] font-bold uppercase tracking-wide text-emerald-700"
                        >
                            <i class="ti ti-thumb-up text-[8px]" />
                            Recommends
                        </span>
                    </div>
                    <blockquote
                        class="mt-2 font-editorial text-[10px] font-normal leading-relaxed text-ink/75"
                    >
                        “Showed up on time and explained everything clearly.”
                    </blockquote>
                    <figcaption class="mt-2 flex flex-wrap items-center gap-1 text-[8px]">
                        <span class="font-bold text-ink/70">Mrs. Adeyemi</span>
                        <span class="font-medium text-ink/35">· 14 Jul 2026</span>
                    </figcaption>
                </figure>
            </article>
        </div>
    </div>
</template>

<script setup>
defineProps({
    variant: {
        type: String,
        required: true,
        validator: (v) =>
            ['signup', 'setup', 'log', 'send', 'review', 'share'].includes(v),
    },
});
</script>
