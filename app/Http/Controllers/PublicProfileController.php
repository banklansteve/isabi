<?php

namespace App\Http\Controllers;

use App\Support\JobCategories;
use App\Support\PublicArtisan;
use App\Support\Seo;
use App\Support\SeoSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicProfileController extends Controller
{
    public function show(Request $request, string $slug): Response|RedirectResponse
    {
        $user = PublicArtisan::locate($slug);

        if ($user instanceof RedirectResponse) {
            return $user;
        }

        $viewer = $request->user();
        $viewerIsOwner = $viewer !== null && (int) $viewer->id === (int) $user->id;

        $workLogs = $user->workLogs()
            ->publiclyVisible()
            ->with(['media', 'review'])
            ->orderByDesc('worked_on')
            ->orderByDesc('id')
            ->limit(120)
            ->get()
            ->map(fn ($log) => [
                'uid' => $log->uid,
                'reference' => $log->reference,
                'slug' => $log->slug,
                'public_url' => ($user->slug && $log->reference)
                    ? route('public.job', [$user->slug, $log->reference])
                    : null,
                'detail_url' => $viewerIsOwner
                    ? route('work-log.show', $log->uid)
                    : (($user->slug && $log->reference)
                        ? route('public.job', [$user->slug, $log->reference])
                        : null),
                'description' => $log->description,
                'job_category' => $log->job_category,
                'job_subcategory' => $log->job_subcategory,
                'category_label' => JobCategories::displayLabel($log->job_category, $log->job_subcategory),
                'worked_on' => $log->worked_on?->toDateString(),
                'worked_on_label' => $log->worked_on?->timezone(config('app.display_timezone'))->format('j M Y'),
                'service_label' => collect([
                    $log->service_city,
                    $log->service_lga,
                    $log->service_state,
                ])->filter()->implode(', ') ?: null,
                'media' => $log->media->take(8)->map(fn ($m) => [
                    'id' => $m->id,
                    'url' => $m->url(),
                    'thumb_url' => $m->thumbUrl(1000),
                    'preview_url' => $m->previewUrl(1600),
                    'poster_url' => $m->posterUrl(800),
                    'kind' => $m->kind,
                    'original_name' => $m->original_name,
                ])->values(),
                'review' => ($log->review && $log->review->isPubliclyVisible()) ? [
                    'rating' => (float) $log->review->rating,
                    'would_recommend' => $log->review->would_recommend,
                    'comment' => $log->review->comment,
                    'client_display_name' => $log->review->client_display_name,
                    'referred_by' => $log->review->referred_by,
                    'photo_url' => $log->review->photoUrl(),
                    'photo_thumb_url' => $log->review->photoThumbUrl(700),
                    'photo_preview_url' => $log->review->photoPreviewUrl(),
                    'submitted_at_label' => $log->review->submitted_at
                        ?->timezone(config('app.display_timezone'))
                        ->format('j M Y'),
                ] : null,
            ])
            ->values();

        $reviewCount = $user->reviews()->publiclyVisible()->count();
        $avgRating = $reviewCount > 0
            ? round((float) $user->reviews()->publiclyVisible()->avg('rating'), 1)
            : null;
        $verifiedWorks = $user->workLogs()->publiclyVisible()->whereHas('review', fn ($query) => $query->publiclyVisible())->count();
        $jobsCount = $user->workLogs()->publiclyVisible()->count();

        // How long clients take to respond once a job is logged. A short,
        // consistent turnaround reads as unprompted rather than chased.
        $responseHours = $user->workLogs()
            ->whereHas('review', fn ($query) => $query->publiclyVisible())
            ->with('review:id,work_log_id,submitted_at')
            ->get(['id', 'created_at'])
            ->map(function ($log) {
                $submitted = $log->review?->submitted_at;

                return $submitted && $submitted->greaterThanOrEqualTo($log->created_at)
                    ? $log->created_at->diffInHours($submitted)
                    : null;
            })
            ->filter(fn ($hours) => $hours !== null);

        $avgResponseHours = $responseHours->isNotEmpty()
            ? (int) round($responseHours->avg())
            : null;

        $wa = preg_replace('/\D+/', '', (string) $user->whatsapp) ?? '';
        if (str_starts_with($wa, '0') && strlen($wa) === 11) {
            $wa = '234'.substr($wa, 1);
        }

        // Count unique public visits per browser session — skip the owner previewing their page.
        if (! $viewerIsOwner) {
            $sessionKey = 'profile_viewed:'.$user->id;
            if (! $request->session()->has($sessionKey)) {
                $user->increment('public_page_views');
                $request->session()->put($sessionKey, true);
            }
        }

        $profileUrl = $user->publicUrl() ?: url('/p/'.$user->slug);
        $seoDescription = filled($user->bio)
            ? $user->bio
            : $user->displayBusinessName()
                .' — '.($user->trade ?: 'artisan')
                .(filled($user->state) ? ' in '.$user->state : '')
                .' with '.($jobsCount === 1 ? '1 finished job' : $jobsCount.' finished jobs')
                .($reviewCount > 0 ? ' and '.$reviewCount.' client '.($reviewCount === 1 ? 'review' : 'reviews') : '')
                .' on Isabi.';

        $reviewedForSchema = $user->workLogs()
            ->publiclyVisible()
            ->whereHas('review', fn ($query) => $query->publiclyVisible())
            ->with('review')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        app(Seo::class)
            ->title($user->displayBusinessName().' · '.($user->trade ?: 'Artisan'))
            ->description($seoDescription)
            ->canonical($profileUrl)
            ->image($user->avatar_url)
            ->type('profile')
            ->schema(SeoSchema::artisan($user, $avgRating, $reviewCount, $reviewedForSchema))
            ->schema(SeoSchema::breadcrumbs([
                ['name' => 'Home', 'url' => url('/')],
                ['name' => 'Artisans', 'url' => route('public.directory')],
                ['name' => $user->displayBusinessName(), 'url' => $profileUrl],
            ]));

        return Inertia::render('Public/Profile', [
            'profile' => [
                'business_name' => $user->displayBusinessName(),
                'slug' => $user->slug,
                'public_url' => $user->publicUrl(),
                'trade' => $user->trade,
                'skills' => array_values($user->skills ?? []),
                'state' => $user->state,
                'lga' => $user->lga,
                'bio' => $user->bio,
                'avatar_url' => $user->avatar_url,
                'logo_url' => $user->logo_url,
                'embed_url' => $user->slug ? route('embed.profile', $user->slug) : null,
                'area_label' => collect([$user->lga, $user->state])->filter()->implode(', ') ?: null,
                'whatsapp_url' => $wa !== '' ? "https://wa.me/{$wa}" : null,
                'review_count' => $reviewCount,
                'avg_rating' => $avgRating,
                'jobs_count' => $jobsCount,
                'verified_works' => $verifiedWorks,
                // Self-declared. The public page must label these as unverified.
                'credentials' => collect($user->credentials ?? [])
                    ->map(fn ($entry) => [
                        'title' => $entry['title'] ?? null,
                        'issuer' => $entry['issuer'] ?? null,
                        'reference' => $entry['reference'] ?? null,
                        'year' => $entry['year'] ?? null,
                    ])
                    ->filter(fn ($entry) => filled($entry['title']))
                    ->values(),
                'years_active' => $user->yearsActive(),
                'coverage_areas' => array_values($user->coverage_areas ?? []),
                'coverage_note' => $user->coverage_note,
                'coverage_summary' => $user->coverageSummary(),
                'review_rate' => $jobsCount > 0
                    ? (int) round(($verifiedWorks / $jobsCount) * 100)
                    : null,
                'avg_response_hours' => $avgResponseHours,
            ],
            'timeline' => $workLogs,
            'viewerIsOwner' => $viewerIsOwner,
            'quoteUrl' => $user->slug ? route('public.profile.quote', $user->slug) : null,
        ]);
    }
}
