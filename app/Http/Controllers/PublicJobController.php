<?php

namespace App\Http\Controllers;

use App\Support\JobCategories;
use App\Support\JobPublicLocator;
use App\Support\PublicArtisan;
use App\Support\Seo;
use App\Support\SeoSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicJobController extends Controller
{
    public function show(Request $request, string $slug, string $context, string $ref): Response|RedirectResponse
    {
        $artisan = PublicArtisan::locate($slug);

        if ($artisan instanceof RedirectResponse) {
            $target = rtrim($artisan->getTargetUrl(), '/');

            return redirect()->to($target.'/'.$context.'/'.$ref, 301);
        }

        return $this->render($request, $artisan, $context, $ref);
    }

    /**
     * Legacy /p/{artisan}/{segment} → 301 to /p/{artisan}/{context}/{ref}
     */
    public function showLegacy(Request $request, string $slug, string $job): Response|RedirectResponse
    {
        $artisan = PublicArtisan::locate($slug);

        if ($artisan instanceof RedirectResponse) {
            $target = rtrim($artisan->getTargetUrl(), '/');

            return redirect()->to($target.'/'.$job, 301);
        }

        $viewer = $request->user();

        if ($denied = PublicArtisan::denyUnlessVisible($artisan, $viewer)) {
            return $denied;
        }

        $log = JobPublicLocator::findForArtisan((int) $artisan->id, $job);

        if (! $log) {
            abort(404);
        }

        $params = $log->publicRouteParams();
        if (! $params) {
            abort(404);
        }

        return redirect()->route('public.job', $params, 301);
    }

    private function render(Request $request, $artisan, string $context, string $ref): Response|RedirectResponse
    {
        $viewer = $request->user();

        if ($denied = PublicArtisan::denyUnlessVisible($artisan, $viewer)) {
            return $denied;
        }

        $log = JobPublicLocator::findForArtisan((int) $artisan->id, $context, $ref);

        if (! $log) {
            abort(404);
        }

        $log->load(['media', 'review']);

        $viewerIsOwner = $viewer !== null && (int) $viewer->id === (int) $artisan->id;

        if (! $log->isPubliclyVisible() && ! $viewerIsOwner) {
            abort(404);
        }

        $params = $log->publicRouteParams();
        if (
            $params
            && ($context !== $params[1] || strtolower($ref) !== strtolower($params[2]))
        ) {
            return redirect()->route('public.job', $params, 301);
        }

        $seo = app(Seo::class);
        $url = $log->publicUrl() ?: ($params ? route('public.job', $params) : null);
        // Public SEO title uses catalog labels only — never free-text subject/client details.
        $title = JobCategories::displayLabel($log->job_category, $log->job_subcategory)
            ?: 'Completed work';
        $location = collect([$log->service_city, $log->service_lga, $log->service_state])
            ->filter()
            ->implode(', ');
        $description = filled($log->review?->comment)
            ? $log->review->comment
            : ($log->description ?: $title).' by '.$artisan->displayBusinessName()
                .($location ? ' in '.$location : '').'.';

        $firstImage = $log->media->first(fn ($m) => $m->isImage());
        $reviewPhoto = $log->review?->isPubliclyVisible() ? $log->review->photoUrl() : null;

        $wa = preg_replace('/\D+/', '', (string) $artisan->whatsapp) ?? '';

        $seo->title($title.' · '.$artisan->displayBusinessName())
            ->description($description)
            ->canonical($url)
            ->image($firstImage?->previewUrl(1200) ?: ($reviewPhoto ?: $artisan->avatar_url))
            ->type('article');

        if (! $log->isPubliclySubstantial()) {
            $seo->noindex();
        }

        $seo->schema(SeoSchema::job($log, $artisan))
            ->schema(SeoSchema::breadcrumbs([
                ['name' => 'Home', 'url' => url('/')],
                ['name' => 'Artisans', 'url' => route('public.directory')],
                ['name' => $artisan->displayBusinessName(), 'url' => route('public.profile', $artisan->slug)],
                ['name' => $title, 'url' => $url],
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
                'embed_url' => $params ? route('embed.job', $params) : null,
                'subject' => $log->displayTitle(),
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
                    'photo_url' => $log->review->photoUrl(),
                    'photo_thumb_url' => $log->review->photoThumbUrl(700),
                    'photo_preview_url' => $log->review->photoPreviewUrl(),
                    'submitted_at_label' => $log->review->submitted_at
                        ?->timezone(config('app.display_timezone'))
                        ->format('j M Y'),
                    'submitted_at' => $log->review->submitted_at?->toDateString(),
                ] : null,
            ],
            'viewerIsOwner' => $viewerIsOwner,
            'quoteUrl' => $params ? route('public.job.quote', $params) : null,
        ]);
    }
}
