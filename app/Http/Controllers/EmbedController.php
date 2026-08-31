<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\WorkLog;
use App\Support\JobCategories;
use App\Support\PublicArtisan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class EmbedController extends Controller
{
    public function profile(string $slug): View|RedirectResponse|Response
    {
        $artisan = PublicArtisan::locate($slug);

        if ($artisan instanceof RedirectResponse) {
            return $artisan;
        }

        $logs = WorkLog::query()
            ->where('user_id', $artisan->id)
            ->publiclyVisible()
            ->with(['review', 'media'])
            ->orderByDesc('worked_on')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $reviews = Review::query()
            ->where('user_id', $artisan->id)
            ->whereHas('workLog', fn ($query) => $query->publiclyVisible())
            ->publiclyVisible()
            ->latest('submitted_at')
            ->limit(3)
            ->get();

        $avgRating = Review::query()
            ->where('user_id', $artisan->id)
            ->whereHas('workLog', fn ($query) => $query->publiclyVisible())
            ->publiclyVisible()
            ->avg('rating');

        return response()
            ->view('embed.profile', [
                'artisan' => $artisan,
                'logs' => $logs,
                'reviews' => $reviews,
                'avgRating' => $avgRating ? round((float) $avgRating, 1) : null,
                'reviewCount' => Review::query()
                    ->where('user_id', $artisan->id)
                    ->whereHas('workLog', fn ($query) => $query->publiclyVisible())
                    ->publiclyVisible()
                    ->count(),
                'publicUrl' => $artisan->publicUrl(),
                'embedUrl' => route('embed.profile', $artisan->slug),
            ])
            ->header('Content-Security-Policy', 'frame-ancestors *');
    }

    public function job(string $slug, string $job): View|RedirectResponse|Response
    {
        $artisan = PublicArtisan::locate($slug);

        if ($artisan instanceof RedirectResponse) {
            return $artisan;
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

        if (! $log->isPubliclyVisible()) {
            abort(404);
        }

        return response()
            ->view('embed.job', [
                'artisan' => $artisan,
                'log' => $log,
                'categoryLabel' => JobCategories::displayLabel($log->job_category, $log->job_subcategory),
                'publicUrl' => $log->publicUrl(),
                'quoteUrl' => route('public.job.quote', [$artisan->slug, $log->reference]),
            ])
            ->header('Content-Security-Policy', 'frame-ancestors *');
    }
}
