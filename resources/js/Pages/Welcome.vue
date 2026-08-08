<template>
    <Head title="Proof that gets you the next job" />

    <div class="min-h-dvh bg-pale text-ink">
        <!-- Sticky nav -->
        <header
            class="fixed inset-x-0 top-0 z-50 transition-[background-color,box-shadow,border-color] duration-300 ease-out"
            :class="
                navScrolled || mobileNavOpen
                    ? 'border-b border-ink/10 bg-white text-ink shadow-nav'
                    : 'border-b border-transparent bg-transparent text-white'
            "
        >
            <div
                class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-5 py-3 sm:px-8 lg:px-10"
                style="padding-top: max(0.75rem, env(safe-area-inset-top))"
            >
                <Link
                    href="/"
                    class="shrink-0 font-display text-[1.35rem] font-extrabold tracking-tight transition-colors duration-300"
                    :class="navScrolled || mobileNavOpen ? 'text-ink' : 'text-white'"
                >
                    Isabi
                </Link>

                <nav class="hidden items-center gap-1 lg:flex lg:gap-2">
                    <a
                        href="#how-it-works"
                        class="tap-target inline-flex items-center px-3 text-sm font-semibold transition-colors duration-300"
                        :class="
                            navScrolled
                                ? 'text-ink/70 hover:text-ink'
                                : 'text-white/75 hover:text-white'
                        "
                    >
                        How it works
                    </a>
                    <a
                        href="#pricing"
                        class="tap-target inline-flex items-center px-3 text-sm font-semibold transition-colors duration-300"
                        :class="
                            navScrolled
                                ? 'text-ink/70 hover:text-ink'
                                : 'text-white/75 hover:text-white'
                        "
                    >
                        Pricing
                    </a>
                </nav>

                <div class="hidden items-center gap-2 lg:flex">
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="route('dashboard')"
                            class="tap-target inline-flex items-center justify-center rounded-2xl bg-coral px-5 text-sm font-semibold text-white transition-colors duration-native hover:bg-coral-deep"
                        >
                            Open dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            v-if="canLogin"
                            :href="route('login')"
                            class="tap-target inline-flex items-center justify-center px-3 text-sm font-semibold transition-colors duration-300"
                            :class="
                                navScrolled
                                    ? 'text-ink/75 hover:text-ink'
                                    : 'text-white/80 hover:text-white'
                            "
                        >
                            Sign in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="tap-target inline-flex items-center justify-center rounded-2xl bg-coral px-5 text-sm font-semibold text-white transition-colors duration-native hover:bg-coral-deep"
                        >
                            Get started
                        </Link>
                    </template>
                </div>

                <div class="flex items-center gap-2 lg:hidden">
                    <Link
                        v-if="canRegister && !$page.props.auth.user"
                        :href="route('register')"
                        class="tap-target inline-flex items-center justify-center rounded-2xl bg-coral px-4 text-sm font-semibold text-white"
                    >
                        Get started
                    </Link>
                    <button
                        type="button"
                        class="tap-target inline-flex items-center justify-center rounded-xl px-3 text-sm font-semibold transition-colors duration-300"
                        :class="navScrolled || mobileNavOpen ? 'text-ink' : 'text-white'"
                        :aria-expanded="mobileNavOpen"
                        aria-controls="mobile-nav"
                        @click="mobileNavOpen = !mobileNavOpen"
                    >
                        {{ mobileNavOpen ? 'Close' : 'Menu' }}
                    </button>
                </div>
            </div>

            <Transition name="soft">
                <div
                    v-if="mobileNavOpen"
                    id="mobile-nav"
                    class="border-t border-ink/10 bg-white px-5 py-4 text-ink lg:hidden"
                >
                    <div class="mx-auto flex max-w-7xl flex-col gap-1">
                        <a
                            href="#how-it-works"
                            class="tap-target flex items-center rounded-xl px-3 text-sm font-semibold text-ink/75 transition-colors hover:text-ink"
                            @click="mobileNavOpen = false"
                        >
                            How it works
                        </a>
                        <a
                            href="#pricing"
                            class="tap-target flex items-center rounded-xl px-3 text-sm font-semibold text-ink/75 transition-colors hover:text-ink"
                            @click="mobileNavOpen = false"
                        >
                            Pricing
                        </a>
                        <Link
                            :href="route('faq')"
                            class="tap-target flex items-center rounded-xl px-3 text-sm font-semibold text-ink/75 transition-colors hover:text-ink"
                            @click="mobileNavOpen = false"
                        >
                            FAQ
                        </Link>
                        <Link
                            v-if="canLogin && !$page.props.auth.user"
                            :href="route('login')"
                            class="tap-target flex items-center rounded-xl px-3 text-sm font-semibold text-ink/75"
                            @click="mobileNavOpen = false"
                        >
                            Sign in
                        </Link>
                        <Link
                            v-else-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="tap-target flex items-center rounded-xl px-3 text-sm font-semibold text-ink/75"
                            @click="mobileNavOpen = false"
                        >
                            Dashboard
                        </Link>
                    </div>
                </div>
            </Transition>
        </header>

        <!-- Hero -->
        <section
            class="relative isolate overflow-hidden bg-ink text-white pb-[calc(5.5rem+env(safe-area-inset-bottom))] pt-20 lg:pb-0 lg:pt-24"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(120%_80%_at_10%_-10%,rgba(47,111,237,0.45),transparent_55%),radial-gradient(90%_70%_at_90%_10%,rgba(255,106,61,0.18),transparent_50%),linear-gradient(180deg,#0B1F3A_0%,#123B72_58%,#0B1F3A_100%)]"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.07]"
                style="
                    background-image: linear-gradient(
                            rgba(255, 255, 255, 0.45) 1px,
                            transparent 1px
                        ),
                        linear-gradient(
                            90deg,
                            rgba(255, 255, 255, 0.45) 1px,
                            transparent 1px
                        );
                    background-size: 48px 48px;
                    mask-image: radial-gradient(
                        ellipse at 50% 0%,
                        black 20%,
                        transparent 72%
                    );
                "
                aria-hidden="true"
            />

            <div class="relative mx-auto w-full max-w-7xl px-5 sm:px-8 lg:px-10">
                <div
                    class="grid items-center gap-10 pb-10 pt-6 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)] lg:gap-14 lg:pb-20 lg:pt-10"
                >
                    <div class="max-w-xl">
                        <h1
                            class="animate-fade-up text-display-xl text-balance text-white"
                        >
                            Proof that gets you the next job
                        </h1>
                        <p
                            class="animate-fade-up mt-5 max-w-md text-base font-medium leading-relaxed text-white/75 sm:text-lg [animation-delay:80ms]"
                        >
                            Log your work, collect real client reviews, and share one page that
                            new customers can trust.
                        </p>
                        <div
                            class="animate-fade-up mt-8 hidden items-center gap-3 lg:flex [animation-delay:200ms]"
                        >
                            <Link
                                v-if="canRegister && !$page.props.auth.user"
                                :href="route('register')"
                                class="tap-target inline-flex items-center justify-center rounded-2xl bg-coral px-6 text-base font-semibold text-white transition-colors duration-native hover:bg-coral-deep"
                            >
                                Create your free page
                            </Link>
                            <Link
                                v-else-if="$page.props.auth.user"
                                :href="route('dashboard')"
                                class="tap-target inline-flex items-center justify-center rounded-2xl bg-coral px-6 text-base font-semibold text-white transition-colors duration-native hover:bg-coral-deep"
                            >
                                Open dashboard
                            </Link>
                            <p class="text-sm font-medium text-white/55">
                                Free to start · No card needed
                            </p>
                        </div>
                    </div>

                    <div
                        class="animate-fade-up relative mx-auto w-full max-w-[22rem] lg:max-w-none [animation-delay:180ms]"
                        aria-hidden="true"
                    >
                        <div
                            class="absolute -inset-8 rounded-[2.5rem] bg-[radial-gradient(circle_at_50%_40%,rgba(255,106,61,0.22),transparent_62%)] blur-2xl"
                        />
                        <div
                            class="relative overflow-hidden rounded-[2rem] bg-gradient-to-b from-white/12 to-white/5 p-3 shadow-soft ring-1 ring-white/15 backdrop-blur-sm"
                        >
                            <div class="overflow-hidden rounded-[1.35rem] bg-pale text-ink">
                                <Transition name="soft" mode="out-in">
                                    <div :key="activeProfile.id">
                                        <div class="bg-ink px-5 pb-6 pt-5 text-white">
                                            <div class="flex items-start gap-3">
                                                <div
                                                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-base font-display text-lg font-bold"
                                                >
                                                    {{ activeProfile.initials }}
                                                </div>
                                                <div class="min-w-0 pt-0.5">
                                                    <p class="truncate font-display text-lg font-bold tracking-tight">
                                                        {{ activeProfile.name }}
                                                    </p>
                                                    <p class="mt-0.5 text-sm font-medium text-white/65">
                                                        {{ activeProfile.trade }} · {{ activeProfile.area }}
                                                    </p>
                                                    <div class="mt-2 flex items-center gap-1.5">
                                                        <StarRow :rating="5" size="sm" class="text-coral" />
                                                        <span class="text-xs font-semibold text-white/70">
                                                            {{ activeProfile.rating }} ·
                                                            {{ activeProfile.reviews }} reviews
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-3 px-4 py-4">
                                            <div
                                                v-for="(job, index) in activeProfile.jobs"
                                                :key="index"
                                                class="rounded-2xl bg-white px-3.5 py-3 shadow-card"
                                            >
                                                <div class="flex items-center justify-between gap-2">
                                                    <p class="text-sm font-semibold tracking-tight">
                                                        {{ job.title }}
                                                    </p>
                                                    <p class="text-xs font-medium text-ink/45">
                                                        {{ job.date }}
                                                    </p>
                                                </div>
                                                <p class="mt-1 text-xs leading-relaxed text-ink/55">
                                                    {{ job.note }}
                                                </p>
                                                <div
                                                    v-if="job.review"
                                                    class="mt-2.5 border-t border-tint pt-2.5"
                                                >
                                                    <StarRow
                                                        :rating="job.stars"
                                                        size="sm"
                                                        class="text-coral"
                                                    />
                                                    <p class="mt-1.5 text-xs font-medium leading-relaxed text-ink/70">
                                                        “{{ job.review }}”
                                                    </p>
                                                    <p class="mt-1 text-[0.7rem] font-semibold text-coral-deep">
                                                        Verified client
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="px-4 pb-4">
                                            <div
                                                class="tap-target flex w-full items-center justify-center rounded-2xl bg-[#25D366] text-sm font-semibold text-white"
                                            >
                                                Contact via WhatsApp
                                            </div>
                                        </div>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section id="how-it-works" class="scroll-mt-24 px-5 py-16 sm:px-8 lg:px-10 lg:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="font-display text-display-md text-ink">How it works</h2>
                    <p class="mt-3 text-base font-medium leading-relaxed text-ink/60 sm:text-lg">
                        Four short steps. Built for phones, WhatsApp, and the way trades actually get hired.
                    </p>
                </div>

                <ol class="mt-12 grid gap-4 sm:grid-cols-2 sm:gap-5 xl:grid-cols-4 xl:gap-6">
                    <li
                        v-for="(step, index) in steps"
                        :key="step.title"
                        class="group rounded-[1.5rem] bg-white p-6 shadow-card transition-shadow duration-300 ease-out hover:shadow-card-hover sm:p-7"
                    >
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-2xl bg-tint text-deep transition-colors duration-300 group-hover:bg-ink group-hover:text-white"
                        >
                            <component :is="step.icon" class="h-5 w-5" />
                        </div>
                        <p class="mt-5 text-xs font-bold uppercase tracking-[0.14em] text-base">
                            Step 0{{ index + 1 }}
                        </p>
                        <h3 class="mt-2 font-display text-xl font-bold tracking-tight text-ink">
                            {{ step.title }}
                        </h3>
                        <p class="mt-2.5 text-sm font-medium leading-relaxed text-ink/60">
                            {{ step.body }}
                        </p>
                    </li>
                </ol>
            </div>
        </section>

        <!-- What's on your page -->
        <section id="on-your-page" class="scroll-mt-24 border-t border-tint/80 px-5 py-16 sm:px-8 lg:px-10 lg:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="max-w-2xl">
                    <h2 class="font-display text-display-md text-ink">What's on your page</h2>
                    <p class="mt-3 text-base font-medium leading-relaxed text-ink/60 sm:text-lg">
                        Not a brochure. A living record of jobs done — and the clients who stood behind them.
                    </p>
                </div>

                <div class="mt-12 flex flex-col gap-4 sm:gap-5">
                    <article
                        v-for="feature in pageFeatures"
                        :key="feature.title"
                        class="flex gap-4 rounded-[1.5rem] bg-white p-5 shadow-card transition-shadow duration-300 ease-out hover:shadow-card-hover sm:gap-5 sm:p-7"
                    >
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl sm:h-14 sm:w-14"
                            :class="feature.accent"
                        >
                            <i :class="['ti', feature.icon, 'text-[1.35rem] sm:text-[1.5rem]']" aria-hidden="true" />
                        </div>
                        <div class="min-w-0 pt-0.5">
                            <h3 class="font-display text-xl font-bold tracking-tight text-ink sm:text-2xl">
                                {{ feature.title }}
                            </h3>
                            <p class="mt-1 text-base font-medium italic tracking-tight text-ink/80 sm:text-lg">
                                {{ feature.subtitle }}
                            </p>
                            <p class="mt-2.5 text-sm font-medium leading-relaxed text-ink/55 sm:text-[0.95rem]">
                                {{ feature.body }}
                            </p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- Sample profiles -->
        <section id="samples" class="scroll-mt-24 border-t border-tint/80 bg-white px-5 py-16 sm:px-8 lg:px-10 lg:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="max-w-2xl">
                    <h2 class="font-display text-display-md text-ink">
                        Pages that already look like track records
                    </h2>
                    <p class="mt-3 text-base font-medium leading-relaxed text-ink/60 sm:text-lg">
                        Electricians, plumbers, tailors — finished work, dated, and backed by the people
                        who hired them.
                    </p>
                </div>

                <div
                    class="mt-8 inline-flex w-full max-w-md rounded-2xl bg-pale p-1 shadow-card sm:w-auto"
                    role="tablist"
                    aria-label="Example trades"
                >
                    <button
                        v-for="profile in profiles"
                        :key="profile.id"
                        type="button"
                        role="tab"
                        :aria-selected="activeProfileId === profile.id"
                        class="tap-target relative flex-1 rounded-xl px-4 text-sm font-semibold transition-all duration-300 ease-out sm:flex-none sm:px-5"
                        :class="
                            activeProfileId === profile.id
                                ? 'bg-white text-ink shadow-card'
                                : 'text-ink/45 hover:text-ink/70'
                        "
                        @click="activeProfileId = profile.id"
                    >
                        {{ profile.trade }}
                    </button>
                </div>

                <div class="relative mt-8 overflow-hidden">
                    <Transition name="soft" mode="out-in">
                        <div
                            :key="activeProfile.id"
                            class="grid items-stretch gap-5 lg:grid-cols-12 lg:gap-6"
                        >
                            <!-- Profile preview -->
                            <div
                                class="rounded-[1.75rem] bg-pale p-6 shadow-card sm:p-8 lg:col-span-7"
                            >
                                <div class="flex flex-wrap items-start gap-4">
                                    <div
                                        class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-deep font-display text-xl font-bold text-white"
                                    >
                                        {{ activeProfile.initials }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-display text-2xl font-bold tracking-tight text-ink">
                                            {{ activeProfile.name }}
                                        </p>
                                        <p class="mt-1 text-sm font-semibold text-base">
                                            {{ activeProfile.trade }}
                                        </p>
                                        <p class="mt-1 text-sm font-medium text-ink/50">
                                            {{ activeProfile.area }}
                                        </p>
                                        <div class="mt-3 flex flex-wrap items-center gap-2">
                                            <StarRow :rating="5" size="md" class="text-coral" />
                                            <span class="text-sm font-semibold text-ink/70">
                                                {{ activeProfile.rating }}
                                                <span class="font-medium text-ink/40">
                                                    · {{ activeProfile.reviews }} reviews
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-8 space-y-4">
                                    <div
                                        v-for="(job, index) in activeProfile.jobs"
                                        :key="index"
                                        class="rounded-2xl bg-white p-4 shadow-card sm:p-5"
                                    >
                                        <div class="flex items-baseline justify-between gap-3">
                                            <p class="text-sm font-semibold text-ink sm:text-base">
                                                {{ job.title }}
                                            </p>
                                            <p class="shrink-0 text-xs font-medium text-ink/40">
                                                {{ job.date }}
                                            </p>
                                        </div>
                                        <p class="mt-1.5 text-sm leading-relaxed text-ink/55">
                                            {{ job.note }}
                                        </p>
                                        <div
                                            v-if="job.review"
                                            class="mt-3 border-t border-tint pt-3"
                                        >
                                            <StarRow
                                                :rating="job.stars"
                                                size="sm"
                                                class="text-coral"
                                            />
                                            <p class="mt-2 text-sm font-medium leading-relaxed text-ink/75">
                                                “{{ job.review }}”
                                            </p>
                                            <p class="mt-1.5 text-xs font-semibold text-coral-deep">
                                                Verified client
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Side panel — uses the space properly -->
                            <div
                                class="flex flex-col justify-between gap-8 rounded-[1.75rem] bg-ink p-6 text-white shadow-card sm:p-8 lg:col-span-5"
                            >
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-coral">
                                        Built for mobile
                                    </p>
                                    <h3 class="mt-3 font-display text-2xl font-bold tracking-tight sm:text-3xl">
                                        Built for the thumb, shared on WhatsApp
                                    </h3>
                                    <p class="mt-4 text-sm font-medium leading-relaxed text-white/65 sm:text-base">
                                        Log a job in under 30 seconds. Request a review with one tap.
                                        Your public page becomes the link you send when someone asks,
                                        “Can I see your work?”
                                    </p>
                                </div>

                                <ul class="space-y-4">
                                    <li
                                        v-for="point in sampleHighlights"
                                        :key="point"
                                        class="flex gap-3 rounded-2xl bg-white/5 px-4 py-3.5 ring-1 ring-white/10"
                                    >
                                        <span
                                            class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-coral"
                                        />
                                        <span class="text-sm font-medium leading-relaxed text-white/80">
                                            {{ point }}
                                        </span>
                                    </li>
                                </ul>

                                <div class="border-t border-white/10 pt-6">
                                    <p class="text-sm font-medium text-white/50">
                                        Same page. Different trades. Real client voices.
                                    </p>
                                    <Link
                                        v-if="canRegister && !$page.props.auth.user"
                                        :href="route('register')"
                                        class="tap-target mt-4 inline-flex w-full items-center justify-center rounded-2xl bg-coral px-5 text-sm font-semibold text-white transition-colors duration-native hover:bg-coral-deep sm:w-auto"
                                    >
                                        Create your free page
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </section>

        <!-- Why different -->
        <section id="trust" class="scroll-mt-24 bg-pale px-5 py-16 sm:px-8 lg:px-10 lg:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="mx-auto max-w-3xl text-center">
                    <h2 class="font-display text-display-md text-ink">Why this is different</h2>
                    <blockquote
                        class="mx-auto mt-8 max-w-2xl font-voice text-[1.65rem] leading-snug tracking-tight text-ink sm:text-[2rem] sm:leading-[1.3] lg:text-[2.25rem]"
                    >
                        Anyone can build a page in an afternoon. That's not proof — it's a claim.
                        Isabi is built so the only way to look good on it is to actually be good, over
                        time.
                    </blockquote>
                </div>

                <div class="mt-12 grid gap-4 sm:grid-cols-3 sm:gap-5 lg:gap-6">
                    <article
                        v-for="point in trustPoints"
                        :key="point.title"
                        class="rounded-[1.5rem] bg-white p-6 shadow-card transition-shadow duration-300 ease-out hover:shadow-card-hover sm:p-7"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-tint text-deep"
                            >
                                <i class="ti ti-shield-check text-xl" aria-hidden="true" />
                            </div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-deep/70">
                                Enforced
                            </p>
                        </div>
                        <h3 class="mt-5 font-display text-xl font-bold tracking-tight text-ink">
                            {{ point.title }}
                        </h3>
                        <p class="mt-3 text-sm font-medium leading-relaxed text-ink/60">
                            {{ point.body }}
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <!-- Pricing -->
        <section
            id="pricing"
            class="relative scroll-mt-24 overflow-hidden bg-white px-5 py-16 sm:px-8 lg:px-10 lg:py-24"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(90%_60%_at_50%_0%,rgba(227,236,252,0.9),transparent_58%)]"
                aria-hidden="true"
            />
            <div class="relative mx-auto max-w-7xl">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="font-display text-display-md text-ink">Pricing</h2>
                    <p class="mt-3 text-base font-medium leading-relaxed text-ink/60 sm:text-lg">
                        Start free. Pay only when the page is earning its keep — not before you've
                        logged a single job.
                    </p>
                </div>

                <div class="mt-12 grid auto-rows-fr gap-5 sm:gap-6 lg:grid-cols-3 lg:gap-7">
                    <!-- Free -->
                    <article
                        class="group flex h-full min-h-0 flex-col rounded-[1.75rem] bg-white p-6 ring-1 ring-ink/[0.06] shadow-premium transition-[transform,box-shadow] duration-300 ease-out hover:-translate-y-1.5 hover:shadow-premium-hover sm:p-8"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-bold uppercase tracking-[0.14em] text-base">
                                Free
                            </p>
                            <span class="rounded-full bg-tint px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-deep">
                                Start here
                            </span>
                        </div>
                        <p class="mt-5 font-display text-4xl font-extrabold tracking-tight text-ink sm:text-5xl">
                            ₦0
                        </p>
                        <p class="mt-2.5 text-sm font-medium leading-relaxed text-ink/55">
                            Everything you need to start building proof
                        </p>
                        <ul class="mt-8 flex-1 space-y-4 text-sm font-medium text-ink/75">
                            <li
                                v-for="item in pricingPlans.free.features"
                                :key="item"
                                class="flex gap-3"
                            >
                                <i class="ti ti-check mt-0.5 shrink-0 text-[1.1rem] text-deep" aria-hidden="true" />
                                <span>{{ item }}</span>
                            </li>
                        </ul>
                        <div class="mt-auto border-t border-ink/[0.06] pt-8">
                            <Link
                                v-if="canRegister && !$page.props.auth.user"
                                :href="route('register')"
                                class="tap-target inline-flex w-full items-center justify-center rounded-2xl bg-deep px-5 text-sm font-semibold text-white transition-colors duration-300 hover:bg-ink"
                            >
                                Get started free
                            </Link>
                            <Link
                                v-else-if="$page.props.auth.user"
                                :href="route('dashboard')"
                                class="tap-target inline-flex w-full items-center justify-center rounded-2xl bg-deep px-5 text-sm font-semibold text-white transition-colors duration-300 hover:bg-ink"
                            >
                                Open dashboard
                            </Link>
                        </div>
                    </article>

                    <!-- Pay as you go -->
                    <article
                        class="group flex h-full min-h-0 flex-col rounded-[1.75rem] bg-white p-6 ring-1 ring-ink/[0.06] shadow-premium transition-[transform,box-shadow] duration-300 ease-out hover:-translate-y-1.5 hover:shadow-premium-hover sm:p-8"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-bold uppercase tracking-[0.14em] text-emerald-700">
                                Pay as you go
                            </p>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-emerald-700">
                                Flexible
                            </span>
                        </div>
                        <p class="mt-5 font-display text-4xl font-extrabold tracking-tight text-ink sm:text-5xl">
                            From ₦3,000
                        </p>
                        <p class="mt-2.5 text-sm font-medium leading-relaxed text-ink/55">
                            Top up credits when you need more — no commitment
                        </p>
                        <ul class="mt-8 flex-1 space-y-4 text-sm font-medium text-ink/75">
                            <li
                                v-for="item in pricingPlans.credits.features"
                                :key="item"
                                class="flex gap-3"
                            >
                                <i class="ti ti-check mt-0.5 shrink-0 text-[1.1rem] text-emerald-600" aria-hidden="true" />
                                <span>{{ item }}</span>
                            </li>
                        </ul>
                        <div class="mt-auto border-t border-ink/[0.06] pt-8">
                            <Link
                                v-if="canRegister && !$page.props.auth.user"
                                :href="route('register')"
                                class="tap-target inline-flex w-full items-center justify-center rounded-2xl border border-ink/10 bg-pale px-5 text-sm font-semibold text-ink transition-colors duration-300 hover:border-ink/15 hover:bg-tint"
                            >
                                Start free, top up later
                            </Link>
                            <Link
                                v-else-if="$page.props.auth.user"
                                :href="route('dashboard')"
                                class="tap-target inline-flex w-full items-center justify-center rounded-2xl border border-ink/10 bg-pale px-5 text-sm font-semibold text-ink transition-colors duration-300 hover:border-ink/15 hover:bg-tint"
                            >
                                Open dashboard
                            </Link>
                        </div>
                    </article>

                    <!-- Annual -->
                    <article
                        class="group relative flex h-full min-h-0 flex-col overflow-hidden rounded-[1.75rem] bg-ink p-6 text-white shadow-premium-ink transition-[transform,box-shadow] duration-300 ease-out hover:-translate-y-1.5 hover:shadow-premium-ink-hover sm:p-8"
                    >
                        <div
                            class="pointer-events-none absolute -right-10 -top-10 h-44 w-44 rounded-full bg-coral/25 blur-3xl transition-opacity duration-300 group-hover:opacity-90"
                            aria-hidden="true"
                        />
                        <div
                            class="pointer-events-none absolute -bottom-16 -left-10 h-40 w-40 rounded-full bg-base/20 blur-3xl"
                            aria-hidden="true"
                        />
                        <div class="relative flex items-center justify-between gap-3">
                            <p class="text-sm font-bold uppercase tracking-[0.14em] text-coral">
                                Annual unlock
                            </p>
                            <span class="rounded-full bg-coral/15 px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-coral">
                                Best value
                            </span>
                        </div>
                        <p class="relative mt-5 font-display text-4xl font-extrabold tracking-tight sm:text-5xl">
                            ₦25,000
                            <span class="text-base font-semibold text-white/45">/ year</span>
                        </p>
                        <p class="relative mt-2.5 text-sm font-medium leading-relaxed text-white/60">
                            For artisans who send review requests regularly
                        </p>
                        <ul class="relative mt-8 flex-1 space-y-4 text-sm font-medium text-white/80">
                            <li
                                v-for="item in pricingPlans.annual.features"
                                :key="item"
                                class="flex gap-3"
                            >
                                <i class="ti ti-check mt-0.5 shrink-0 text-[1.1rem] text-coral" aria-hidden="true" />
                                <span>{{ item }}</span>
                            </li>
                        </ul>
                        <div class="relative mt-auto border-t border-white/10 pt-8">
                            <p class="mb-5 rounded-2xl bg-white/5 px-4 py-3.5 text-xs font-medium leading-relaxed text-white/55 ring-1 ring-white/10">
                                Cheaper than topping up if you're actively building your page — no
                                auto-renewal, we'll remind you before it's due.
                            </p>
                            <Link
                                v-if="canRegister && !$page.props.auth.user"
                                :href="route('register')"
                                class="tap-target inline-flex w-full items-center justify-center rounded-2xl bg-coral px-5 text-sm font-semibold text-white transition-colors duration-300 hover:bg-coral-deep"
                            >
                                Get started free
                            </Link>
                            <Link
                                v-else-if="$page.props.auth.user"
                                :href="route('dashboard')"
                                class="tap-target inline-flex w-full items-center justify-center rounded-2xl bg-coral px-5 text-sm font-semibold text-white transition-colors duration-300 hover:bg-coral-deep"
                            >
                                Open dashboard
                            </Link>
                        </div>
                    </article>
                </div>

                <div class="mx-auto mt-12 max-w-xl text-center">
                    <p class="text-sm font-semibold text-ink">
                        Get started free
                    </p>
                    <p class="mt-1.5 text-sm font-medium text-ink/50">
                        No card needed to start. Upgrade anytime from your dashboard.
                    </p>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section id="faq" class="scroll-mt-24 bg-pale px-5 py-16 sm:px-8 lg:px-10 lg:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-xl">
                        <h2 class="font-display text-display-md text-ink">Questions, answered</h2>
                        <p class="mt-3 text-base font-medium leading-relaxed text-ink/60 sm:text-lg">
                            The things people ask before they trust a new tool with their reputation.
                        </p>
                    </div>
                    <Link
                        :href="route('faq')"
                        class="tap-target inline-flex shrink-0 items-center justify-center gap-2 self-start rounded-2xl bg-ink px-5 text-sm font-semibold text-white transition-colors duration-300 hover:bg-deep lg:self-auto"
                    >
                        See all FAQs
                        <i class="ti ti-arrow-right text-lg" aria-hidden="true" />
                    </Link>
                </div>

                <div class="mt-10 grid gap-5 lg:mt-12 lg:grid-cols-12 lg:gap-8">
                    <!-- Question picker -->
                    <div
                        class="lg:col-span-5"
                        role="tablist"
                        aria-label="Frequently asked questions"
                    >
                        <div
                            class="-mx-5 flex gap-2 overflow-x-auto px-5 pb-1 [scrollbar-width:none] lg:mx-0 lg:flex-col lg:gap-2 lg:overflow-visible lg:px-0 [&::-webkit-scrollbar]:hidden"
                        >
                            <button
                                v-for="(item, index) in faqs"
                                :key="item.question"
                                type="button"
                                role="tab"
                                :aria-selected="activeFaq === index"
                                :aria-controls="`faq-panel-${index}`"
                                class="tap-target group relative flex min-w-[16.5rem] shrink-0 items-start gap-3 rounded-2xl px-4 py-3.5 text-left transition-all duration-300 ease-out lg:min-w-0 lg:w-full sm:px-5"
                                :class="
                                    activeFaq === index
                                        ? 'bg-white text-ink shadow-premium ring-1 ring-ink/[0.06]'
                                        : 'bg-transparent text-ink/55 hover:bg-white/70 hover:text-ink/80'
                                "
                                @click="activeFaq = index"
                            >
                                <span
                                    class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg font-display text-xs font-bold transition-colors duration-300"
                                    :class="
                                        activeFaq === index
                                            ? 'bg-ink text-white'
                                            : 'bg-white/80 text-ink/40 group-hover:text-ink/60'
                                    "
                                >
                                    {{ String(index + 1).padStart(2, '0') }}
                                </span>
                                <span class="pt-0.5 text-sm font-semibold leading-snug tracking-tight sm:text-[0.95rem]">
                                    {{ item.question }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Answer stage -->
                    <div class="lg:col-span-7">
                        <div
                            class="relative min-h-[18rem] overflow-hidden rounded-[1.75rem] bg-white p-6 shadow-premium ring-1 ring-ink/[0.06] sm:min-h-[20rem] sm:p-8 lg:min-h-[22rem] lg:p-10"
                        >
                            <div
                                class="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-tint/80 blur-3xl"
                                aria-hidden="true"
                            />
                            <Transition name="faq-panel" mode="out-in">
                                <div
                                    :id="`faq-panel-${activeFaq}`"
                                    :key="activeFaq"
                                    role="tabpanel"
                                    class="relative"
                                >
                                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-base">
                                        Answer
                                    </p>
                                    <h3 class="mt-3 font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">
                                        {{ faqs[activeFaq].question }}
                                    </h3>
                                    <p class="mt-5 max-w-xl text-base font-medium leading-relaxed text-ink/60 sm:text-lg sm:leading-relaxed">
                                        {{ faqs[activeFaq].answer }}
                                    </p>
                                </div>
                            </Transition>
                        </div>

                        <div class="mt-5 flex flex-wrap items-center gap-3 sm:mt-6">
                            <p class="text-sm font-medium text-ink/45">
                                Still unsure?
                            </p>
                            <Link
                                :href="route('faq')"
                                class="tap-target inline-flex items-center gap-1.5 text-sm font-semibold text-deep transition-colors duration-300 hover:text-ink"
                            >
                                Browse the full FAQ
                                <i class="ti ti-arrow-up-right text-base" aria-hidden="true" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA -->
        <section
            class="relative overflow-hidden bg-gradient-to-br from-[#123B72] via-[#0B1F3A] to-[#0B1F3A] px-5 py-24 text-white sm:px-8 sm:py-28 lg:px-10 lg:py-36"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(90%_80%_at_100%_0%,rgba(255,106,61,0.22),transparent_45%),radial-gradient(80%_70%_at_0%_100%,rgba(47,111,237,0.35),transparent_55%)]"
                aria-hidden="true"
            />

            <div
                class="relative mx-auto flex max-w-7xl flex-col items-start justify-between gap-12 lg:flex-row lg:items-center lg:gap-16"
            >
                <div class="max-w-xl">
                    <h2 class="font-display text-display-md text-white">
                        Stop asking clients to take your word for it.
                    </h2>
                    <p class="mt-5 text-base font-medium leading-relaxed text-white/70 sm:text-lg">
                        Create your page free. Log the work. Let the reviews do the convincing.
                    </p>
                </div>

                <div class="w-full shrink-0 sm:w-auto">
                    <Link
                        v-if="canRegister && !$page.props.auth.user"
                        :href="route('register')"
                        class="tap-target inline-flex min-h-[3.5rem] w-full items-center justify-center rounded-2xl bg-coral px-10 text-lg font-bold text-white shadow-[0_12px_32px_rgba(255,106,61,0.35)] transition-[transform,background-color,box-shadow] duration-300 hover:-translate-y-0.5 hover:bg-coral-deep hover:shadow-[0_16px_40px_rgba(255,106,61,0.42)] sm:min-h-[3.75rem] sm:w-auto sm:px-12 sm:text-xl"
                    >
                        Create your free page
                    </Link>
                    <Link
                        v-else-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="tap-target inline-flex min-h-[3.5rem] w-full items-center justify-center rounded-2xl bg-coral px-10 text-lg font-bold text-white shadow-[0_12px_32px_rgba(255,106,61,0.35)] transition-[transform,background-color,box-shadow] duration-300 hover:-translate-y-0.5 hover:bg-coral-deep hover:shadow-[0_16px_40px_rgba(255,106,61,0.42)] sm:min-h-[3.75rem] sm:w-auto sm:px-12 sm:text-xl"
                    >
                        Open dashboard
                    </Link>
                </div>
            </div>
        </section>

        <SiteFooter
            :pad-for-mobile-cta="true"
            :can-register="canRegister"
        />

        <!-- Mobile sticky CTA -->
        <div
            class="fixed inset-x-0 bottom-0 z-40 border-t border-ink/5 bg-white/95 px-4 pt-3 shadow-sticky backdrop-blur-md lg:hidden"
            style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))"
        >
            <Link
                v-if="canRegister && !$page.props.auth.user"
                :href="route('register')"
                class="tap-target flex w-full items-center justify-center rounded-2xl bg-coral text-base font-semibold text-white transition-colors duration-native active:bg-coral-deep"
            >
                Create your free page
            </Link>
            <Link
                v-else-if="$page.props.auth.user"
                :href="route('dashboard')"
                class="tap-target flex w-full items-center justify-center rounded-2xl bg-coral text-base font-semibold text-white transition-colors duration-native active:bg-coral-deep"
            >
                Open dashboard
            </Link>
            <p class="mt-2 text-center text-[0.7rem] font-medium text-ink/40">
                Free · Email verification only
            </p>
        </div>
    </div>
</template>

<script setup>
import SiteFooter from '@/Components/SiteFooter.vue';
import { computed, h, onMounted, onUnmounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
        default: false,
    },
    canRegister: {
        type: Boolean,
        default: false,
    },
});

const navScrolled = ref(false);
const mobileNavOpen = ref(false);
const activeFaq = ref(0);

const onScroll = () => {
    navScrolled.value = window.scrollY > 12;
};

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('scroll', onScroll);
});

const StarRow = (props) => {
    const sizeClass = props.size === 'md' ? 'h-3.5 w-3.5' : 'h-3 w-3';

    return h(
        'div',
        {
            class: ['flex items-center gap-0.5', props.class],
            'aria-hidden': 'true',
        },
        Array.from({ length: 5 }, (_, i) =>
            h(
                'svg',
                {
                    key: i,
                    class: sizeClass,
                    viewBox: '0 0 20 20',
                    fill: i < props.rating ? 'currentColor' : 'none',
                    stroke: 'currentColor',
                    'stroke-width': i < props.rating ? '0' : '1.5',
                },
                [
                    h('path', {
                        d: 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z',
                    }),
                ],
            ),
        ),
    );
};

StarRow.props = {
    rating: { type: Number, default: 5 },
    size: { type: String, default: 'sm' },
    class: { type: [String, Array, Object], default: '' },
};

const iconProps = {
    fill: 'none',
    stroke: 'currentColor',
    'stroke-width': '1.5',
    'stroke-linecap': 'round',
    'stroke-linejoin': 'round',
    viewBox: '0 0 24 24',
};

const IconUserPlus = () =>
    h('svg', iconProps, [
        h('path', { d: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2' }),
        h('circle', { cx: '9', cy: '7', r: '4' }),
        h('path', { d: 'M19 8v6M22 11h-6' }),
    ]);

const IconClipboard = () =>
    h('svg', iconProps, [
        h('rect', { x: '8', y: '2', width: '8', height: '4', rx: '1' }),
        h('path', { d: 'M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2' }),
        h('path', { d: 'M9 12h6M9 16h4' }),
    ]);

const IconMessage = () =>
    h('svg', iconProps, [
        h('path', { d: 'M7.9 20A9 9 0 1 0 4 16.1L2 22z' }),
        h('path', { d: 'M8 12h.01M12 12h.01M16 12h.01' }),
    ]);

const IconShare = () =>
    h('svg', iconProps, [
        h('circle', { cx: '18', cy: '5', r: '3' }),
        h('circle', { cx: '6', cy: '12', r: '3' }),
        h('circle', { cx: '18', cy: '19', r: '3' }),
        h('path', { d: 'M8.59 13.51l6.83 3.98M15.41 6.51l-6.82 3.98' }),
    ]);

const steps = [
    {
        title: 'Build your page',
        body: "Sign up in minutes — verify your email, add your trade and area, and you're live.",
        icon: IconUserPlus,
    },
    {
        title: 'Log every job',
        body: 'After each job, note what you did — under 30 seconds. Photo optional.',
        icon: IconClipboard,
    },
    {
        title: 'Collect real reviews',
        body: 'Send a WhatsApp link. Clients rate and write — their words, never yours.',
        icon: IconMessage,
    },
    {
        title: 'Win new customers',
        body: 'Share your link or QR. New customers see your work and message instantly.',
        icon: IconShare,
    },
];

const pageFeatures = [
    {
        title: 'Work log',
        subtitle: 'Every job, dated and real',
        body: 'A running timeline of what you did and when. Not a portfolio dump — a track record that builds itself, job after job.',
        icon: 'ti-timeline',
        accent: 'bg-blue-50 text-blue-600',
    },
    {
        title: 'Client reviews',
        subtitle: 'Feedback tied to the work',
        body: "Sent through a private link once the job's done. Each review sits next to the job it's about, in the client's own words — unedited.",
        icon: 'ti-message-star',
        accent: 'bg-emerald-50 text-emerald-600',
    },
    {
        title: 'Vouch chain',
        subtitle: '"Who sent you here?"',
        body: 'Clients can credit who referred them. Trust passed hand to hand, now visible on your page — not just claimed, traced.',
        icon: 'ti-link',
        accent: 'bg-violet-50 text-violet-600',
    },
    {
        title: 'QR code',
        subtitle: 'Your page, always on hand',
        body: 'One scan opens your full profile. Print it on a van, a shop wall, or a card — so people check you out before they call.',
        icon: 'ti-qrcode',
        accent: 'bg-amber-50 text-amber-600',
    },
];

const sampleHighlights = [
    'Work log timeline with dates and optional photos',
    'Testimonials locked to specific finished jobs',
    'One WhatsApp contact button for new customers',
];

const trustPoints = [
    {
        title: 'Clients write the reviews',
        body: "Not you. You can't author or edit a single word of a testimonial — it's enforced in the product, not promised on a landing page.",
    },
    {
        title: 'Jobs build up over time',
        body: "There's no shortcut — no uploading ten old photos to look established overnight. Your timeline only grows as you actually work.",
    },
    {
        title: 'Every link is tied to a real job',
        body: "Reviews aren't floating praise — each one is locked to a specific, logged job. No work behind it, no review.",
    },
];

const pricingPlans = {
    free: {
        features: [
            'Public profile with your own link',
            'Unlimited logged jobs',
            '5 client review requests per month',
            'QR code and WhatsApp contact button',
        ],
    },
    credits: {
        features: [
            'Use credits for extra review requests, QR downloads, or a custom link',
            'Buy in packs — the more you get, the more you save per credit',
            'Credits never expire, spend them whenever',
        ],
    },
    annual: {
        features: [
            'Everything in Free',
            'Unlimited review requests, no credits needed',
            'Custom URL slug (isabi.dev/your-name)',
            'Priority support as we grow features',
        ],
    },
};

const faqs = [
    {
        question: 'Is Isabi really free to start?',
        answer: 'Yes. Creating your page, logging jobs, and sharing your profile are all free — no card required at signup. You only pay if you want more than 5 client review requests a month, or extras like a custom link.',
    },
    {
        question: 'Do I need a bank card or debit account to sign up?',
        answer: "No. Signup is free and needs nothing but your email address and a few business details. When you do choose to pay later to unlock more services, you can use bank transfer, USSD, or card — whichever you're comfortable with. We never save your card details or charge you automatically.",
    },
    {
        question: 'Can I write my own reviews, or ask a client to say something nice?',
        answer: "No — and that's by design. Only clients can submit a review, through a private link tied to a specific logged job. You can't write, edit, or approve what they say. This is what makes reviews on Isabi worth more than a screenshot.",
    },
    {
        question: "What if my client doesn't have WhatsApp or isn't tech-savvy?",
        answer: "The review link works in any browser — WhatsApp is just the easiest way to send it, since most clients already have it. They don't need to download an app or create an account to leave a review.",
    },
    {
        question: 'Is my profile public? Can anyone see it?',
        answer: "Yes, that's the point. Your page is built to be shared — with a link or QR code — so new customers can check your work and reviews before they contact you. You control what you log; you can't hide reviews you don't like once a client submits them.",
    },
];

const profiles = [
    {
        id: 'electrician',
        name: 'Segun Adebayo',
        initials: 'SA',
        trade: 'Electrician',
        area: 'Lekki, Lagos',
        rating: '4.9',
        reviews: 18,
        jobs: [
            {
                title: 'Kitchen rewiring',
                date: '12 Jul',
                note: 'Replaced sockets and fitted new consumer unit.',
                review: 'Showed up on time and explained everything clearly.',
                stars: 5,
            },
            {
                title: 'Office lighting install',
                date: '3 Jul',
                note: 'Ceiling lights across two meeting rooms.',
                review: 'Clean install and finished ahead of schedule.',
                stars: 5,
            },
        ],
    },
    {
        id: 'plumber',
        name: 'Ibrahim Musa',
        initials: 'IM',
        trade: 'Plumber',
        area: 'Gwarinpa, Abuja',
        rating: '5.0',
        reviews: 12,
        jobs: [
            {
                title: 'Bathroom pipe repair',
                date: '20 Jul',
                note: 'Fixed leaking mixer and resealed shower tray.',
                review: 'Neat work. Left the place cleaner than he met it.',
                stars: 5,
            },
            {
                title: 'Water heater install',
                date: '8 Jul',
                note: 'New 50L heater with proper pressure valve.',
                review: 'Explained the options and fitted it the same day.',
                stars: 5,
            },
        ],
    },
    {
        id: 'tailor',
        name: 'Chioma Okeke',
        initials: 'CO',
        trade: 'Tailor',
        area: 'Kano City',
        rating: '4.8',
        reviews: 24,
        jobs: [
            {
                title: 'Agbada for wedding',
                date: '15 Jul',
                note: 'Full traditional set with embroidery.',
                review: 'Fit was perfect. Delivered two days early.',
                stars: 5,
            },
            {
                title: 'Corporate shirts ×6',
                date: '28 Jun',
                note: 'Custom fitted shirts for office wear.',
                review: 'Great attention to detail on the collars and cuffs.',
                stars: 5,
            },
        ],
    },
];

const activeProfileId = ref(profiles[0].id);

const activeProfile = computed(
    () => profiles.find((profile) => profile.id === activeProfileId.value) ?? profiles[0],
);
</script>

<style scoped>
.soft-enter-active,
.soft-leave-active {
    transition:
        opacity 220ms ease-out,
        transform 220ms ease-out;
}

.soft-enter-from {
    opacity: 0;
    transform: translateY(8px);
}

.soft-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}

.faq-panel-enter-active,
.faq-panel-leave-active {
    transition:
        opacity 240ms cubic-bezier(0.22, 1, 0.36, 1),
        transform 240ms cubic-bezier(0.22, 1, 0.36, 1);
}

.faq-panel-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.faq-panel-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>
