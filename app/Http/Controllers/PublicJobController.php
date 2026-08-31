<?php

namespace App\Http\Controllers;

use App\Models\WorkLog;
use App\Support\JobCategories;
use App\Support\PublicArtisan;
use App\Support\Seo;
use App\Support\SeoSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicJobController extends Controller
{
    public function show(Request $request, string $slug, string $job): Response|RedirectResponse
    {
        $artisan = PublicArtisan::locate($slug);

        if ($artisan instanceof RedirectResponse) {
            $target = $artisan->getTargetUrl();

            return redirect()->to(rtrim($target, '/').'/'.$job, 301);
        }

        $log = WorkLog::query()
            ->where('user_id', $artisan->id)
            ->where(function ($query) use ($job) {
                $query->where('reference', strtolower($job))
                    ->orWhere('slug', $job)
                    ->orWhere('uid', $job);
            })
            ->with(['media', 'review'])
            ->firstOrFail();

        $viewer = $request->user();
        $viewerIsOwner = $viewer !== null && (int) $viewer->id === (int) $artisan->id;

        if (! $log->isPubliclyVisible() && ! $viewerIsOwner) {
            abort(404);
        }

        if (filled($log->reference) && strtolower($job) !== (string) $log->reference) {
            return redirect()->route('public.job', [$artisan->slug, $log->reference], 301);
        }

        $seo = app(Seo::class);
        $url = route('public.job', [$artisan->slug, $log->reference]);
        $location = collect([$log->service_city, $log->service_lga, $log->service_state])
            ->filter()
            ->implode(', ');
        $description = filled($log->review?->comment)
            ? $log->review->comment
            : $log->description.' by '.$artisan->displayBusinessName()
                .($location ? ' in '.$location : '').'.';

        $firstImage = $log->media->first(fn ($m) => $m->isImage());

        $wa = preg_replace('/\D+/', '', (string) $artisan->whatsapp) ?? '';

        $seo->title($log->description.' · '.$artisan->displayBusinessName())
            ->description($description)
            ->canonical($url)
            ->image($firstImage?->previewUrl(1200) ?: $artisan->avatar_url)
            ->type('article');

        if (! $log->isPubliclySubstantial()) {
            $seo->noindex();
        }

        $seo->schema(SeoSchema::job($log, $artisan))
            ->schema(SeoSchema::breadcrumbs([
                ['name' => 'Home', 'url' => url('/')],
                ['name' => 'Artisans', 'url' => route('public.directory')],
                ['name' => $artisan->displayBusinessName(), 'url' => route('public.profile', $artisan->slug)],
                ['name' => $log->description, 'url' => $url],
            ]));

        return Inertia::render('Public/Job', [
            'profile' => [
                'business_name' => $artisan->displayBusinessName(),
                'slug' => $artisan->slug,
                'public_url' => $artisan->publicUrl(),
                'trade' => $artisan->trade,
                'avatar_url' => $artisan->avatar_url,
                'logo_url' => $artisan->logo_url,
                'whatsapp_url' => $wa !== '' ? "https://wa.me/{$wa}" : null,
                'area_label' => collect([$artisan->lga, $artisan->state])->filter()->implode(', ') ?: null,
            ],
            'job' => [
                'uid' => $log->uid,
                'reference' => $log->reference,
                'slug' => $log->slug,
                'public_url' => $url,
                'embed_url' => route('embed.job', [$artisan->slug, $log->reference]),
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
                'media' => $log->media->map(fn ($m) => [
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
                    'submitted_at' => $log->review->submitted_at?->toDateString(),
                ] : null,
            ],
            'viewerIsOwner' => $viewer !== null && (int) $viewer->id === (int) $artisan->id,
            'quoteUrl' => route('public.job.quote', [$artisan->slug, $log->reference]),
        ]);
    }
}
