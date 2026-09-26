<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ArtisanDirectory;
use App\Support\JobCategories;
use App\Support\Seo;
use App\Support\SeoSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ArtisanDirectoryController extends Controller
{
    /**
     * Soft cap for the client-side directory payload.
     * Filters/search/sort run entirely in the browser against this set.
     */
    public const DIRECTORY_CAP = 300;

    public function index(Request $request): Response
    {
        return $this->render($request);
    }

    public function trade(Request $request, string $tradeSlug): Response
    {
        $trade = ArtisanDirectory::tradeFromSlug($tradeSlug);

        if ($trade === null) {
            throw new NotFoundHttpException;
        }

        return $this->render($request, $trade);
    }

    public function tradeState(Request $request, string $tradeSlug, string $stateSlug): Response
    {
        $trade = ArtisanDirectory::tradeFromSlug($tradeSlug);
        $state = ArtisanDirectory::stateFromSlug($stateSlug);

        if ($trade === null || $state === null) {
            throw new NotFoundHttpException;
        }

        return $this->render($request, $trade, $state);
    }

    private function render(Request $request, ?string $lockedTrade = null, ?string $lockedState = null): Response
    {
        $base = User::query()
            ->publiclyListed()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->whereHas('workLogs')
            ->withCount(['workLogs', 'reviews'])
            ->withAvg(['reviews' => fn ($q) => $q->publiclyVisible()], 'rating');

        if ($lockedTrade !== null) {
            $base->where(function ($query) use ($lockedTrade) {
                $query->where('trade', $lockedTrade)
                    ->orWhereJsonContains('trades', $lockedTrade);
            });
        }

        if ($lockedState !== null) {
            $base->where('state', $lockedState);
        }

        $totalEligible = (clone $base)->count();

        $users = (clone $base)
            ->orderByDesc('reviews_count')
            ->orderByDesc('work_logs_count')
            ->orderBy('business_name')
            ->limit(self::DIRECTORY_CAP)
            ->get();

        $artisans = $users->map(function (User $user) {
            $category = JobCategories::parentFor($user->trade) ?: 'Other';

            return [
                'business_name' => $user->displayBusinessName(),
                'slug' => $user->slug,
                'url' => $user->publicUrl(),
                'trade' => $user->trade ?: 'Other',
                'category' => $category,
                'avatar_url' => $user->avatar_url,
                'state' => $user->state ?: null,
                'lga' => $user->lga ?: null,
                'area_label' => collect([$user->lga, $user->state])->filter()->implode(', ') ?: null,
                'jobs_count' => (int) $user->work_logs_count,
                'review_count' => (int) $user->reviews_count,
                'avg_rating' => $user->reviews_avg_rating !== null
                    ? round((float) $user->reviews_avg_rating, 1)
                    : null,
            ];
        })->values()->all();

        $trades = collect($artisans)
            ->pluck('trade')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $categories = collect($artisans)
            ->pluck('category')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $states = collect($artisans)
            ->pluck('state')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        // On landings, filter options still include the locked values even if the
        // capped set is empty — so the selects show the intended context.
        if ($lockedTrade !== null && ! in_array($lockedTrade, $trades, true)) {
            $trades[] = $lockedTrade;
            sort($trades);
        }
        if ($lockedState !== null && ! in_array($lockedState, $states, true)) {
            $states[] = $lockedState;
            sort($states);
        }

        $landing = $this->landingCopy($lockedTrade, $lockedState);
        $canonical = $this->canonicalUrl($lockedTrade, $lockedState);

        $breadcrumbs = [
            ['name' => 'Home', 'url' => url('/')],
            ['name' => 'Artisans', 'url' => route('public.directory')],
        ];

        if ($lockedTrade !== null) {
            $breadcrumbs[] = [
                'name' => ArtisanDirectory::tradePlural($lockedTrade),
                'url' => route('public.directory.trade', ArtisanDirectory::tradeSlug($lockedTrade)),
            ];
        }

        if ($lockedState !== null && $lockedTrade !== null) {
            $breadcrumbs[] = [
                'name' => $lockedState,
                'url' => $canonical,
            ];
        }

        app(Seo::class)
            ->title($landing['title'])
            ->description($landing['description'])
            ->canonical($canonical)
            ->schema(SeoSchema::breadcrumbs($breadcrumbs))
            ->schema(SeoSchema::itemList(
                collect($artisans)
                    ->take(24)
                    ->map(fn (array $row) => [
                        'name' => $row['business_name'],
                        'url' => $row['url'],
                    ])
                    ->all(),
            ));

        return Inertia::render('Public/Directory', [
            'artisans' => $artisans,
            'filterOptions' => [
                'trades' => $trades,
                'categories' => $categories !== [] ? $categories : JobCategories::parents(),
                'states' => $states !== [] ? $states : ($lockedState ? [$lockedState] : []),
            ],
            'locked' => [
                'trade' => $lockedTrade,
                'state' => $lockedState,
            ],
            'landing' => $landing,
            'relatedLandings' => $this->relatedLandings($lockedTrade, $lockedState, $artisans),
            'meta' => [
                'loaded' => count($artisans),
                'total_eligible' => $totalEligible,
                'capped' => $totalEligible > self::DIRECTORY_CAP,
                'cap' => self::DIRECTORY_CAP,
            ],
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    /**
     * Internal SEO links: popular trade×state pairs and sibling states for a trade landing.
     *
     * @param  list<array<string, mixed>>  $artisans
     * @return list<array{label: string, url: string}>
     */
    private function relatedLandings(?string $trade, ?string $state, array $artisans): array
    {
        if ($trade !== null && $state === null) {
            $states = collect($artisans)
                ->pluck('state')
                ->filter()
                ->unique()
                ->take(12)
                ->values();

            if ($states->isEmpty()) {
                $states = collect(['Lagos', 'Abuja', 'Rivers', 'Ogun', 'Oyo', 'Kano'])->take(6);
            }

            return $states->map(fn (string $s) => [
                'label' => ArtisanDirectory::tradePlural($trade).' in '.($s === 'FCT' ? 'Abuja' : $s),
                'url' => route('public.directory.trade-state', [
                    ArtisanDirectory::tradeSlug($trade),
                    ArtisanDirectory::stateSlug($s),
                ]),
            ])->all();
        }

        if ($trade !== null && $state !== null) {
            $siblings = collect(['Lagos', 'FCT', 'Rivers', 'Ogun', 'Oyo', 'Kano', 'Kaduna', 'Enugu'])
                ->reject(fn (string $s) => $s === $state)
                ->take(6)
                ->map(fn (string $s) => [
                    'label' => ArtisanDirectory::tradePlural($trade).' in '.($s === 'FCT' ? 'Abuja' : $s),
                    'url' => route('public.directory.trade-state', [
                        ArtisanDirectory::tradeSlug($trade),
                        ArtisanDirectory::stateSlug($s),
                    ]),
                ])
                ->all();

            return $siblings;
        }

        // Main directory: seed crawl paths for high-intent landings.
        $seeds = [
            ['Electrician', 'Lagos'],
            ['Plumber', 'Lagos'],
            ['Carpenter', 'Lagos'],
            ['Painter / Decorator', 'Lagos'],
            ['Electrician', 'FCT'],
            ['Plumber', 'Rivers'],
            ['Fashion designer / Tailor', 'Lagos'],
            ['Tiler', 'Lagos'],
            ['Welder / Fabricator', 'Lagos'],
            ['AC / Refrigeration technician', 'Lagos'],
            ['Solar installer', 'Lagos'],
            ['Generator technician', 'Lagos'],
        ];

        return collect($seeds)->map(function (array $pair) {
            [$t, $s] = $pair;

            return [
                'label' => ArtisanDirectory::tradePlural($t).' in '.($s === 'FCT' ? 'Abuja' : $s),
                'url' => route('public.directory.trade-state', [
                    ArtisanDirectory::tradeSlug($t),
                    ArtisanDirectory::stateSlug($s),
                ]),
            ];
        })->all();
    }

    /**
     * @return array{title: string, description: string, heading: string, subheading: string}
     */
    private function landingCopy(?string $trade, ?string $state): array
    {
        if ($trade !== null && $state !== null) {
            $plural = ArtisanDirectory::tradePlural($trade);

            return [
                'title' => "{$plural} in {$state}",
                'description' => "Find {$plural} in {$state} with public pages built from finished jobs and reviews written by real clients on Kraftrack.",
                'heading' => "{$plural} in {$state}",
                'subheading' => 'Public pages built from finished jobs and reviews written by clients — not self-written testimonials.',
            ];
        }

        if ($trade !== null) {
            $plural = ArtisanDirectory::tradePlural($trade);

            return [
                'title' => "{$plural} on Kraftrack",
                'description' => "Browse {$plural} across Nigeria whose public pages are built from finished jobs and client-written reviews.",
                'heading' => $plural,
                'subheading' => 'Public pages built from finished jobs and reviews written by clients — not self-written testimonials.',
            ];
        }

        $copy = config('seo.pages.directory');

        return [
            'title' => $copy['title'],
            'description' => $copy['description'],
            'heading' => 'Artisans with real proof of work',
            'subheading' => 'Public pages built from finished jobs and reviews written by clients — not self-written testimonials.',
        ];
    }

    private function canonicalUrl(?string $trade, ?string $state): string
    {
        if ($trade !== null && $state !== null) {
            return route('public.directory.trade-state', [
                ArtisanDirectory::tradeSlug($trade),
                ArtisanDirectory::stateSlug($state),
            ]);
        }

        if ($trade !== null) {
            return route('public.directory.trade', ArtisanDirectory::tradeSlug($trade));
        }

        return route('public.directory');
    }
}
