<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Seo;
use App\Support\SeoSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class ArtisanDirectoryController extends Controller
{
    /**
     * Soft cap for the client-side directory payload.
     * Filters/search/sort run entirely in the browser against this set.
     */
    public const DIRECTORY_CAP = 300;

    public function __invoke(Request $request): Response
    {
        $base = User::query()
            ->artisans()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->whereHas('workLogs')
            ->withCount(['workLogs', 'reviews']);

        $totalEligible = (clone $base)->count();

        $users = (clone $base)
            ->orderByDesc('reviews_count')
            ->orderByDesc('work_logs_count')
            ->orderBy('business_name')
            ->limit(self::DIRECTORY_CAP)
            ->get();

        $artisans = $users->map(fn (User $user) => [
            'business_name' => $user->displayBusinessName(),
            'slug' => $user->slug,
            'url' => $user->publicUrl(),
            'trade' => $user->trade ?: 'Other',
            'avatar_url' => $user->avatar_url,
            'state' => $user->state ?: null,
            'lga' => $user->lga ?: null,
            'area_label' => collect([$user->lga, $user->state])->filter()->implode(', ') ?: null,
            'jobs_count' => (int) $user->work_logs_count,
            'review_count' => (int) $user->reviews_count,
        ])->values()->all();

        $trades = collect($artisans)
            ->pluck('trade')
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

        $copy = config('seo.pages.directory');

        app(Seo::class)
            ->title($copy['title'])
            ->description($copy['description'])
            ->canonical(route('public.directory'))
            ->schema(SeoSchema::breadcrumbs([
                ['name' => 'Home', 'url' => url('/')],
                ['name' => 'Artisans', 'url' => route('public.directory')],
            ]))
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
                'states' => $states,
            ],
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
}
