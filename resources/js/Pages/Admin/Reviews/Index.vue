<template>
    <Head title="Reviews" />

    <AdminChrome title="Reviews" eyebrow="Platform reputation" />
        <div v-if="tab === 'health'" class="space-y-4">
            <AdminRangePicker :range="range" />
            <AdminAreaChart
                title="Review request-to-completion"
                hint="Percent of sent links that got a response"
                :series="completionSeries"
            />
            <div class="grid gap-4 xl:grid-cols-2">
                <AdminHistogram title="Rating distribution" hint="Healthy platforms cluster at 4–5" :buckets="ratingBuckets" />
                <AdminAreaChart title="Flagged reviews" hint="Trust-and-safety volume" :series="flaggedSeries" />
            </div>
        </div>

        <div v-else>
            <div class="mb-4 flex flex-col gap-3">
                <AdminRangePicker :range="range" />
                <div class="flex flex-col gap-2 lg:flex-row lg:flex-wrap lg:items-center">
                    <input
                        v-model="list.q.value"
                        type="search"
                        placeholder="Search comment, client, artisan…"
                        class="w-full rounded-xl border border-ink/10 bg-white px-3.5 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15 lg:max-w-sm"
                    />
                    <select
                        v-model="ratingFilter"
                        class="rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                    >
                        <option value="">All ratings</option>
                        <option v-for="star in [5, 4, 3, 2, 1]" :key="star" :value="String(star)">{{ star }} stars</option>
                    </select>
                    <select v-model="list.sort.value" class="rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15">
                        <option value="date_desc">Newest first</option>
                        <option value="date_asc">Oldest first</option>
                        <option value="rating_desc">Highest rated</option>
                        <option value="rating_asc">Lowest rated</option>
                    </select>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-premium ring-1 ring-ink/[0.05]">
                <AdminEmpty
                    v-if="!list.pageItems.value.length"
                    title="No reviews match"
                    description="Try a different search, rating, or date range."
                    icon="ti ti-star"
                />
                <ul v-else class="divide-y divide-ink/[0.06]">
                    <li v-for="review in list.pageItems.value" :key="review.id">
                        <button
                            type="button"
                            class="flex w-full items-start gap-3 px-4 py-3.5 text-left transition-colors hover:bg-pale/80 active:bg-tint sm:px-5"
                            @click="open = review"
                        >
                            <span
                                class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-tint text-sm font-bold text-deep"
                            >
                                {{ review.rating }}★
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate text-sm font-bold text-ink">{{ review.client || 'Client' }}</p>
                                    <span
                                        v-if="review.flagged"
                                        class="rounded-full bg-coral-tint px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-coral-deep"
                                    >
                                        Flagged
                                    </span>
                                    <span
                                        v-if="review.hidden"
                                        class="rounded-full bg-pale px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-ink/40"
                                    >
                                        Hidden
                                    </span>
                                </div>
                                <p class="mt-0.5 line-clamp-2 text-[13px] font-medium leading-relaxed text-ink/55">
                                    {{ snapshot(review.comment) }}
                                </p>
                                <p class="mt-1 text-[12px] font-medium text-ink/35">
                                    {{ review.user?.name }} · {{ review.submitted_at }}
                                </p>
                            </div>
                            <i class="ti ti-chevron-right mt-2 text-ink/20" aria-hidden="true" />
                        </button>
                    </li>
                </ul>
            </div>
            <AdminClientPager
                :page="list.page.value"
                :pages="list.pageCount.value"
                :total="list.total.value"
                :per-page="list.perPage"
                @update:page="list.page.value = $event"
            />
        </div>

        <AdminDrawer
            :open="!!open"
            :title="open ? `${open.rating}★ from ${open.client || 'a client'}` : ''"
            eyebrow="Review"
            @close="open = null"
        >
            <div v-if="open" class="space-y-5">
                <div class="rounded-2xl bg-pale p-4">
                    <p class="whitespace-pre-wrap text-[15px] font-medium leading-relaxed text-ink">{{ open.comment || 'No written comment.' }}</p>
                    <p class="mt-3 text-[12px] font-semibold text-ink/40">{{ open.submitted_at }}</p>
                </div>

                <dl class="grid grid-cols-2 gap-3 text-[13px]">
                    <div>
                        <dt class="font-semibold text-ink/40">Artisan</dt>
                        <dd class="mt-0.5 font-bold text-ink">{{ open.user?.name }}</dd>
                        <dd class="text-ink/50">{{ open.user?.email }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-ink/40">Recommend</dt>
                        <dd class="mt-0.5 font-bold text-ink">{{ open.would_recommend ? 'Yes' : 'No' }}</dd>
                    </div>
                    <div class="col-span-2" v-if="open.job">
                        <dt class="font-semibold text-ink/40">Job</dt>
                        <dd class="mt-0.5 font-medium text-ink">{{ open.job.description }}</dd>
                        <dd class="text-ink/45">{{ open.job.client_name }} · {{ open.job.worked_on }}</dd>
                    </div>
                </dl>

                <img
                    v-if="open.photo_url"
                    :src="open.photo_url"
                    alt=""
                    class="w-full rounded-2xl object-cover"
                />

                <div class="flex flex-wrap gap-2">
                    <button
                        v-if="!open.flagged"
                        type="button"
                        class="rounded-xl bg-pale px-3 py-2 text-xs font-bold text-ink/60 transition-colors hover:bg-tint active:scale-[0.98]"
                        @click="moderate('flag')"
                    >
                        Flag
                    </button>
                    <button
                        v-else
                        type="button"
                        class="rounded-xl bg-pale px-3 py-2 text-xs font-bold text-ink/60 transition-colors hover:bg-tint active:scale-[0.98]"
                        @click="moderate('unflag')"
                    >
                        Clear flag
                    </button>
                    <button
                        v-if="!open.hidden"
                        type="button"
                        class="rounded-xl bg-red-50 px-3 py-2 text-xs font-bold text-red-600 transition-colors hover:bg-red-100 active:scale-[0.98]"
                        @click="moderate('hide')"
                    >
                        Hide
                    </button>
                    <Link
                        v-if="open.user?.id"
                        :href="route('admin.users.show', open.user.id)"
                        class="rounded-xl bg-pale px-3 py-2 text-xs font-bold text-ink/60"
                    >
                        View artisan
                    </Link>
                </div>

                <form v-if="open.user?.id" class="rounded-2xl bg-pale p-4" @submit.prevent="sendMessage">
                    <h3 class="text-[14px] font-bold text-ink">Message {{ open.user.name }}</h3>
                    <p class="mt-0.5 text-[12px] font-medium text-ink/45">
                        Sends in-app and email about this review.
                    </p>
                    <input
                        v-model="message.subject"
                        type="text"
                        placeholder="Subject"
                        class="mt-3 w-full rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                    />
                    <textarea
                        v-model="message.body"
                        rows="4"
                        placeholder="Keep it specific to this review…"
                        class="mt-2 w-full rounded-xl border border-ink/10 bg-white px-3 py-2.5 text-sm font-medium outline-none focus:border-base focus:ring-4 focus:ring-base/15"
                    />
                    <button
                        type="submit"
                        class="mt-3 rounded-xl bg-base-action px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-base-hover disabled:opacity-50"
                        :disabled="message.processing"
                    >
                        {{ message.processing ? 'Sending…' : 'Send message' }}
                    </button>
                </form>
            </div>
        </AdminDrawer>
</template>

<script setup>
import AdminAreaChart from '@/Components/Admin/AdminAreaChart.vue';
import AdminClientPager from '@/Components/Admin/AdminClientPager.vue';
import AdminDrawer from '@/Components/Admin/AdminDrawer.vue';
import AdminEmpty from '@/Components/Admin/AdminEmpty.vue';
import AdminHistogram from '@/Components/Admin/AdminHistogram.vue';
import AdminRangePicker from '@/Components/Admin/AdminRangePicker.vue';
import { useAdminTabs } from '@/Composables/useAdminTabs';
import { useClientList } from '@/Composables/useClientList';
import { useDateRange } from '@/Composables/useDateRange';
import { toast } from '@/utils/adminRange';
import AdminChrome from '@/Components/Admin/AdminChrome.vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    reviews: { type: Array, default: () => [] },
    completion: { type: Array, default: () => [] },
    ratings: { type: Array, default: () => [] },
    flagged_trend: { type: Array, default: () => [] },
});

const { tab } = useAdminTabs({ tab: 'all' });
const range = useDateRange('all');
const rows = ref([...props.reviews]);
const open = ref(null);
const ratingFilter = ref('');

watch(
    () => props.reviews,
    (value) => {
        rows.value = [...value];
    },
);

const list = useClientList(
    () => rows.value.filter((review) => {
        if (tab.value === 'flagged' && !review.flagged) return false;
        if (ratingFilter.value && Number(review.rating) !== Number(ratingFilter.value)) return false;
        return range.matches(review.submitted_iso);
    }),
    {
        perPage: 20,
        searchFields: ['comment', 'client', 'user.name', 'user.email', 'job.description'],
        sort: 'date_desc',
        sortMap: { date: 'submitted_iso', rating: 'rating' },
    },
);

watch(ratingFilter, () => {
    list.page.value = 1;
});
watch(() => range.preset.value, () => {
    list.page.value = 1;
});
watch(tab, () => {
    list.page.value = 1;
    open.value = null;
});

const snapshot = (comment) => {
    const text = String(comment || '').trim();
    if (!text) return 'No written comment.';
    return text.length > 110 ? `${text.slice(0, 110)}…` : text;
};

const completionSeries = computed(() =>
    range.series(props.completion.map((row) => ({ ...row, value: row.rate }))),
);
const flaggedSeries = computed(() => range.series(props.flagged_trend));
const ratingBuckets = computed(() => {
    const inWindow = rows.value.filter((review) => range.matches(review.submitted_iso));
    return [1, 2, 3, 4, 5].map((star) => ({
        label: String(star),
        value: inWindow.filter((review) => Math.round(Number(review.rating)) === star).length,
    }));
});

const message = reactive({
    subject: '',
    body: '',
    processing: false,
});

watch(open, (review) => {
    if (!review) return;
    message.subject = `About a recent review on Isabi`;
    message.body = `Hi {{first_name}}, we wanted to follow up on the ${review.rating}★ review from ${review.client || 'a client'}.`;
});

const moderate = async (action) => {
    if (!open.value) return;
    const routes = {
        flag: route('admin.reviews.flag', open.value.id),
        unflag: route('admin.reviews.unflag', open.value.id),
        hide: route('admin.reviews.hide', open.value.id),
    };
    try {
        const { data } = await axios.post(routes[action], action === 'hide' || action === 'flag' ? { reason: 'Flagged from admin console' } : {});
        const current = rows.value.find((row) => row.id === open.value.id);
        if (current && action === 'flag') {
            current.flagged = true;
            open.value.flagged = true;
        }
        if (current && action === 'unflag') {
            current.flagged = false;
            open.value.flagged = false;
        }
        if (current && action === 'hide') {
            current.hidden = true;
            open.value.hidden = true;
        }
        toast(data.toast || { type: 'success', message: 'Updated', title: 'Saved' });
    } catch {
        toast({ type: 'error', title: 'Couldn’t update', message: 'Try that again in a moment.' });
    }
};

const sendMessage = async () => {
    if (!open.value?.user?.id) return;
    message.processing = true;
    try {
        const { data } = await axios.post(route('admin.messaging.store'), {
            audience: 'users',
            title: `Review follow-up · ${open.value.user.name}`,
            subject: message.subject,
            body: message.body,
            channels: ['in_app', 'email'],
            segment: { user_id: open.value.user.id },
            action: 'send',
        });
        toast(data.toast || { type: 'success', title: 'Sent', message: 'The artisan will see this shortly.' });
        message.body = '';
    } catch {
        toast({ type: 'error', title: 'Couldn’t send', message: 'The message was not delivered.' });
    } finally {
        message.processing = false;
    }
};
</script>
