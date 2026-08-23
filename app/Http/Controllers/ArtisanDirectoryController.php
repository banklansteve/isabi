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
    public function __invoke(Request $request): Response
    {
        $page = max(1, (int) $request->integer('page', 1));

        $paginator = User::query()
            ->artisans()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->whereHas('workLogs')
            ->withCount(['workLogs', 'reviews'])
            ->orderByDesc('reviews_count')
            ->orderByDesc('work_logs_count')
            ->paginate(24, ['*'], 'page', $page)
            ->withQueryString()
            ->through(fn (User $user) => [
                'business_name' => $user->displayBusinessName(),
                'slug' => $user->slug,
                'url' => $user->publicUrl(),
                'trade' => $user->trade,
                'avatar_url' => $user->avatar_url,
                'area_label' => collect([$user->lga, $user->state])->filter()->implode(', ') ?: null,
                'jobs_count' => (int) $user->work_logs_count,
                'review_count' => (int) $user->reviews_count,
            ]);

        $copy = config('seo.pages.directory');

        app(Seo::class)
            ->title($copy['title'])
            ->description($copy['description'])
            ->canonical($page > 1 ? route('public.directory', ['page' => $page]) : route('public.directory'))
            ->schema(SeoSchema::breadcrumbs([
                ['name' => 'Home', 'url' => url('/')],
                ['name' => 'Artisans', 'url' => route('public.directory')],
            ]))
            ->schema(SeoSchema::itemList(
                collect($paginator->items())
                    ->map(fn (array $row) => [
                        'name' => $row['business_name'],
                        'url' => $row['url'],
                    ])
                    ->all(),
            ));

        return Inertia::render('Public/Directory', [
            'artisans' => $paginator,
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }
}
