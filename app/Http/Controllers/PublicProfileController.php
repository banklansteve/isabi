<?php

namespace App\Http\Controllers;

use App\Models\ProfileSlugRedirect;
use App\Models\User;
use App\Support\JobCategories;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PublicProfileController extends Controller
{
    public function show(string $slug): Response|RedirectResponse
    {
        $user = User::query()->where('slug', $slug)->first();

        if (! $user) {
            $redirect = ProfileSlugRedirect::query()
                ->where('from_slug', $slug)
                ->with('user')
                ->first();

            if ($redirect?->user?->slug) {
                return redirect()->to('/p/'.$redirect->user->slug, 301);
            }

            abort(404);
        }

        $workLogs = $user->workLogs()
            ->with(['media', 'review'])
            ->orderByDesc('worked_on')
            ->orderByDesc('id')
            ->limit(40)
            ->get()
            ->map(fn ($log) => [
                'uid' => $log->uid,
                'description' => $log->description,
                'job_category' => $log->job_category,
                'job_subcategory' => $log->job_subcategory,
                'category_label' => JobCategories::displayLabel($log->job_category, $log->job_subcategory),
                'worked_on' => $log->worked_on?->toDateString(),
                'worked_on_label' => $log->worked_on?->timezone(config('app.timezone'))->format('j M Y'),
                'service_label' => collect([
                    $log->service_city,
                    $log->service_lga,
                    $log->service_state,
                ])->filter()->implode(', ') ?: null,
                'media' => $log->media->take(4)->map(fn ($m) => [
                    'url' => $m->url(),
                    'kind' => $m->kind,
                ])->values(),
                'review' => $log->review ? [
                    'rating' => $log->review->rating,
                    'comment' => $log->review->comment,
                    'client_display_name' => $log->review->client_display_name,
                    'referred_by' => $log->review->referred_by,
                    'photo_url' => $log->review->photoUrl(),
                    'submitted_at_label' => $log->review->submitted_at
                        ?->timezone(config('app.timezone'))
                        ->format('j M Y'),
                ] : null,
            ])
            ->values();

        $reviewCount = $user->reviews()->count();
        $avgRating = $reviewCount > 0
            ? round((float) $user->reviews()->avg('rating'), 1)
            : null;

        $wa = preg_replace('/\D+/', '', (string) $user->whatsapp) ?? '';
        if (str_starts_with($wa, '0') && strlen($wa) === 11) {
            $wa = '234'.substr($wa, 1);
        }

        return Inertia::render('Public/Profile', [
            'profile' => [
                'business_name' => $user->displayBusinessName(),
                'slug' => $user->slug,
                'trade' => $user->trade,
                'state' => $user->state,
                'lga' => $user->lga,
                'bio' => $user->bio,
                'avatar_url' => $user->avatar_url,
                'area_label' => collect([$user->lga, $user->state])->filter()->implode(', ') ?: null,
                'whatsapp_url' => $wa !== '' ? "https://wa.me/{$wa}" : null,
                'review_count' => $reviewCount,
                'avg_rating' => $avgRating,
                'jobs_count' => $user->workLogs()->count(),
            ],
            'timeline' => $workLogs,
        ]);
    }
}
