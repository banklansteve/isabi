<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteUserRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdateProfileSlugRequest;
use App\Http\Requests\UpdateReviewMessageSettingsRequest;
use App\Models\ProfileSlugRedirect;
use App\Services\CloudinaryMediaService;
use App\Support\ActivityLogger;
use App\Support\JobCategories;
use App\Support\NigeriaLocations;
use App\Support\ProfileSlug;
use App\Support\SkillsCatalog;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'locations' => NigeriaLocations::all(),
            'trades' => JobCategories::tradeLabels(),
            'skillSuggestions' => SkillsCatalog::suggestions(),
            'credentialCatalogue' => config('credentials.groups'),
            'maxCredentials' => (int) config('credentials.max', 6),
            'profile' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'business_name' => $user->business_name,
                'slug' => $user->slug,
                'email' => $user->email,
                'trade' => $user->trade,
                'skills' => array_values($user->skills ?? []),
                'credentials' => array_values($user->credentials ?? []),
                'experience_started_year' => $user->experience_started_year,
                'state' => $user->state,
                'lga' => $user->lga,
                'coverage_areas' => array_values($user->coverage_areas ?? []),
                'coverage_note' => $user->coverage_note,
                'office_address' => $user->office_address,
                'whatsapp' => $user->whatsapp,
                'bio' => $user->bio,
                'avatar_url' => $user->avatar_url,
                'logo_url' => $user->logo_url,
                'public_url' => $user->publicUrl(),
                'embed_url' => $user->slug ? route('embed.profile', $user->slug) : null,
                'slug_changes_remaining' => $user->slugChangesRemaining(),
                'max_slug_changes' => (int) config('profiles.max_slug_changes', 3),
                'review_invite_template' => $user->review_invite_template,
                'review_reminder_template' => $user->review_reminder_template,
                'review_reminder_days' => $user->review_reminder_days ?? (int) config('review_messages.default_reminder_days', 3),
            ],
            'reviewMessageDefaults' => [
                'invite' => (string) config('review_messages.invite'),
                'reminder' => (string) config('review_messages.reminder'),
                'max_length' => (int) config('review_messages.max_template_length', 700),
                'default_reminder_days' => (int) config('review_messages.default_reminder_days', 3),
            ],
        ]);
    }

    public function updateAvatar(Request $request, CloudinaryMediaService $cloudinary): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:5120'],
        ]);

        $user = $request->user();

        try {
            $uploaded = $cloudinary->uploadProfilePhoto($request->file('avatar'), $user->id);
        } catch (RuntimeException $e) {
            return Redirect::route('profile.edit')->with('toast', [
                'type' => 'error',
                'title' => 'Upload failed',
                'message' => $e->getMessage(),
                'duration' => 5000,
            ]);
        }

        if (filled($user->avatar_path)) {
            try {
                $cloudinary->delete($user->avatar_path);
            } catch (RuntimeException) {
                // Ignore cleanup failures — new photo still wins.
            }
        }

        $user->forceFill([
            'avatar_path' => $uploaded['public_id'] ?? $user->avatar_path,
            'avatar_url' => $uploaded['url'] ?? $user->avatar_url,
        ])->save();

        ActivityLogger::log(
            action: 'profile.avatar_updated',
            summary: "{$user->name} updated their profile photo.",
            user: $user,
        );

        return Redirect::route('profile.edit')->with('toast', [
            'type' => 'success',
            'title' => 'Photo updated',
            'message' => 'Your profile photo is live on your page.',
            'duration' => 4200,
        ]);
    }

    public function updateLogo(Request $request, CloudinaryMediaService $cloudinary): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:5120'],
        ]);

        $user = $request->user();

        try {
            $uploaded = $cloudinary->uploadBusinessLogo($request->file('logo'), $user->id);
        } catch (RuntimeException $e) {
            return Redirect::route('profile.edit')->with('toast', [
                'type' => 'error',
                'title' => 'Upload failed',
                'message' => $e->getMessage(),
                'duration' => 5000,
            ]);
        }

        if (filled($user->logo_path)) {
            try {
                $cloudinary->delete($user->logo_path);
            } catch (RuntimeException) {
                // Ignore cleanup failures — new logo still wins.
            }
        }

        $user->forceFill([
            'logo_path' => $uploaded['public_id'] ?? $user->logo_path,
            'logo_url' => $uploaded['url'] ?? $user->logo_url,
        ])->save();

        ActivityLogger::log(
            action: 'profile.logo_updated',
            summary: "{$user->name} updated their business logo.",
            user: $user,
        );

        return Redirect::route('profile.edit')->with('toast', [
            'type' => 'success',
            'title' => 'Logo updated',
            'message' => 'Your logo now appears on exports and embeds.',
            'duration' => 4200,
        ]);
    }

    public function updateReviewMessages(UpdateReviewMessageSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->forceFill([
            'review_invite_template' => $data['review_invite_template'] ?? null,
            'review_reminder_template' => $data['review_reminder_template'] ?? null,
            'review_reminder_days' => array_key_exists('review_reminder_days', $data)
                ? $data['review_reminder_days']
                : $user->review_reminder_days,
        ])->save();

        ActivityLogger::log(
            action: 'profile.review_messages_updated',
            summary: "{$user->name} updated their review WhatsApp message settings.",
            user: $user,
        );

        return Redirect::route('profile.edit')->with('toast', [
            'type' => 'success',
            'title' => 'Messages saved',
            'message' => 'Your review WhatsApp wording is ready for the next send.',
            'duration' => 4200,
        ]);
    }

    /**
     * Update the user's profile information (sectioned).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $section = $data['section'];
        unset($data['section']);

        $user->fill(match ($section) {
            'expertise' => [
                'skills' => $data['skills'] ?? [],
                'credentials' => $data['credentials'] ?? [],
                'experience_started_year' => $data['experience_started_year'] ?? null,
            ],
            'contact' => [
                'state' => $data['state'],
                'lga' => $data['lga'],
                'coverage_areas' => $data['coverage_areas'] ?? [],
                'coverage_note' => $data['coverage_note'] ?? null,
                'office_address' => $data['office_address'],
                'whatsapp' => $data['whatsapp'],
            ],
            default => [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'business_name' => $data['business_name'],
                'trade' => $data['trade'],
                'bio' => $data['bio'] ?? null,
            ],
        });

        $user->save();

        ActivityLogger::log(
            action: 'profile.updated',
            summary: "{$user->name} updated their {$section} profile details.",
            user: $user,
            properties: ['section' => $section],
        );

        $messages = [
            'basics' => [
                'title' => 'Basics saved',
                'message' => 'Your name, trade, and bio are live on your page.',
            ],
            'expertise' => [
                'title' => 'Expertise saved',
                'message' => 'Skills, credentials, and experience are up to date.',
            ],
            'contact' => [
                'title' => 'Reach saved',
                'message' => 'Location and WhatsApp details are ready for clients.',
            ],
        ];

        $copy = $messages[$section] ?? [
            'title' => 'Profile saved',
            'message' => 'Your changes are on your public page.',
        ];

        return Redirect::route('profile.edit')->with('toast', [
            'type' => 'success',
            'title' => $copy['title'],
            'message' => $copy['message'],
            'duration' => 4200,
        ]);
    }

    public function updateSlug(UpdateProfileSlugRequest $request): RedirectResponse
    {
        $user = $request->user();
        $desired = ProfileSlug::normalize($request->validated('slug'));

        if ($desired === $user->slug) {
            return Redirect::route('profile.edit')->with('toast', [
                'type' => 'info',
                'message' => 'That’s already your public URL.',
                'duration' => 3500,
            ]);
        }

        if ($user->slugChangesRemaining() < 1) {
            return Redirect::route('profile.edit')->with('toast', [
                'type' => 'error',
                'message' => 'You’ve used all your public URL changes.',
                'duration' => 4500,
            ]);
        }

        // Ensure uniqueness with numeric suffix if somehow raced.
        $newSlug = ProfileSlug::available($desired, $user->id)
            ? $desired
            : ProfileSlug::uniqueFrom($desired, $user->id);

        $oldSlug = $user->slug;

        if (filled($oldSlug) && $oldSlug !== $newSlug) {
            ProfileSlugRedirect::query()->updateOrCreate(
                ['from_slug' => $oldSlug],
                ['user_id' => $user->id],
            );
            // Point any prior redirects at this user still (they already do via user_id).
        }

        // If someone else previously redirected this slug, clear that claim for the new owner.
        ProfileSlugRedirect::query()
            ->where('from_slug', $newSlug)
            ->delete();

        $user->forceFill([
            'slug' => $newSlug,
            'slug_change_count' => (int) $user->slug_change_count + 1,
            'slug_changed_at' => now(),
        ])->save();

        ActivityLogger::log(
            action: 'profile.slug_changed',
            summary: "{$user->name} changed their public URL to /p/{$newSlug}.",
            user: $user,
            properties: [
                'from' => $oldSlug,
                'to' => $newSlug,
            ],
        );

        return Redirect::route('profile.edit')->with('toast', [
            'type' => 'success',
            'title' => 'Link updated',
            'message' => 'Your public URL is live. Old links still redirect here.',
            'duration' => 5000,
        ]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(DeleteUserRequest $request): RedirectResponse
    {
        $user = $request->user();

        ActivityLogger::log(
            action: 'profile.deleted',
            summary: "{$user->name} deleted their Kraftrack account.",
            user: $user,
            properties: ['email' => $user->email],
        );

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::route('account.goodbye');
    }
}
