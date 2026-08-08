<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublicReviewRequest;
use App\Models\Review;
use App\Models\WorkLog;
use App\Services\CloudinaryMediaService;
use App\Support\ActivityLogger;
use App\Support\JobCategories;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PublicReviewController extends Controller
{
    public function __construct(
        private readonly CloudinaryMediaService $cloudinary,
    ) {}

    public function show(string $token): Response|RedirectResponse
    {
        $workLog = $this->resolveInvite($token);

        if ($workLog->review()->exists()) {
            return redirect()->route('reviews.thanks', $token);
        }

        $artisan = $workLog->user;

        return Inertia::render('Reviews/Form', [
            'token' => $token,
            'artisan' => [
                'name' => $artisan->displayBusinessName(),
                'first_name' => $artisan->first_name ?: $artisan->displayBusinessName(),
                'trade' => $artisan->trade,
                'avatar_url' => $artisan->avatar_url,
                'initials' => mb_strtoupper(
                    mb_substr((string) ($artisan->first_name ?: $artisan->displayBusinessName()), 0, 1)
                    .mb_substr((string) ($artisan->last_name ?: ''), 0, 1)
                ) ?: 'I',
            ],
            'job' => [
                'description' => $workLog->description,
                'job_category' => $workLog->job_category,
                'job_subcategory' => $workLog->job_subcategory,
                'category_label' => JobCategories::displayLabel(
                    $workLog->job_category,
                    $workLog->job_subcategory,
                ),
                'worked_on_label' => $workLog->worked_on?->timezone(config('app.timezone'))->format('j M Y'),
                'service_label' => collect([
                    $workLog->service_city,
                    $workLog->service_lga,
                    $workLog->service_state,
                ])->filter()->implode(', ') ?: null,
            ],
        ]);
    }

    public function store(StorePublicReviewRequest $request, string $token): RedirectResponse
    {
        $workLog = $this->resolveInvite($token);

        if ($workLog->review()->exists()) {
            return redirect()->route('reviews.thanks', $token);
        }

        // Data-level: the artisan must never submit their own testimonial.
        if ($request->user() && (int) $request->user()->id === (int) $workLog->user_id) {
            throw ValidationException::withMessages([
                'rating' => 'You can’t leave a review on your own job. Share the link with your client.',
            ]);
        }

        $data = $request->validated();

        try {
            DB::transaction(function () use ($request, $workLog, $data): void {
                $photoDisk = null;
                $photoPath = null;
                $photoUrl = null;

                if ($request->hasFile('photo')) {
                    $uploaded = $this->cloudinary->uploadReviewPhoto(
                        $request->file('photo'),
                        (int) $workLog->user_id,
                        (string) $workLog->uid,
                    );
                    $photoDisk = 'cloudinary';
                    $photoPath = $uploaded['public_id'];
                    $photoUrl = $uploaded['url'];
                }

                Review::create([
                    'work_log_id' => $workLog->id,
                    'user_id' => $workLog->user_id,
                    'rating' => (int) $data['rating'],
                    'comment' => $data['comment'] ?? null,
                    'client_display_name' => $data['client_display_name'] ?? null,
                    'referred_by' => $data['referred_by'] ?? null,
                    'photo_disk' => $photoDisk,
                    'photo_path' => $photoPath,
                    'photo_url' => $photoUrl,
                    'submitter_ip_hash' => hash('sha256', (string) $request->ip()),
                    'user_agent' => $request->userAgent()
                        ? Str::limit($request->userAgent(), 255, '')
                        : null,
                    'submitted_at' => now(),
                ]);
            });
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'photo' => $e->getMessage(),
            ]);
        }

        ActivityLogger::log(
            action: 'review.received',
            summary: "A client left a {$data['rating']}-star review for {$workLog->user->name}.",
            user: $workLog->user,
            properties: [
                'work_log_uid' => $workLog->uid,
                'rating' => (int) $data['rating'],
            ],
        );

        return redirect()->route('reviews.thanks', $token);
    }

    public function thanks(string $token): Response
    {
        $workLog = WorkLog::query()
            ->where('review_token', $token)
            ->with('user')
            ->firstOrFail();

        $artisan = $workLog->user;

        return Inertia::render('Reviews/Thanks', [
            'artisan' => [
                'name' => $artisan->displayBusinessName(),
                'public_url' => $artisan->publicUrl(),
            ],
        ]);
    }

    private function resolveInvite(string $token): WorkLog
    {
        $workLog = WorkLog::query()
            ->where('review_token', $token)
            ->with('user')
            ->firstOrFail();

        if (
            $workLog->review_token_expires_at
            && $workLog->review_token_expires_at->isPast()
            && ! $workLog->review()->exists()
        ) {
            abort(410, 'This review link has expired. Ask the artisan to send a new one.');
        }

        return $workLog;
    }
}
