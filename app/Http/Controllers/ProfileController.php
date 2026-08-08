<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileSlugRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ProfileSlugRedirect;
use App\Support\ActivityLogger;
use App\Support\NigeriaLocations;
use App\Support\ProfileSlug;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

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
            'trades' => config('trades'),
            'profile' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'business_name' => $user->business_name,
                'slug' => $user->slug,
                'email' => $user->email,
                'trade' => $user->trade,
                'state' => $user->state,
                'lga' => $user->lga,
                'office_address' => $user->office_address,
                'whatsapp' => $user->whatsapp,
                'bio' => $user->bio,
                'public_url' => $user->publicUrl(),
                'slug_changes_remaining' => $user->slugChangesRemaining(),
                'max_slug_changes' => (int) config('profiles.max_slug_changes', 3),
            ],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'business_name' => $data['business_name'],
            'email' => $data['email'],
            'trade' => $data['trade'],
            'state' => $data['state'],
            'lga' => $data['lga'],
            'office_address' => $data['office_address'],
            'whatsapp' => $data['whatsapp'],
            'bio' => $data['bio'] ?? null,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        ActivityLogger::log(
            action: 'profile.updated',
            summary: "{$user->name} updated their account profile details.",
            user: $user,
        );

        return Redirect::route('profile.edit')->with('toast', [
            'type' => 'success',
            'message' => 'Profile saved.',
            'duration' => 4000,
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
            'message' => 'Public URL updated. Old links will redirect here.',
            'duration' => 5000,
        ]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        ActivityLogger::log(
            action: 'profile.deleted',
            summary: "{$user->name} deleted their Isabi account.",
            user: $user,
            properties: ['email' => $user->email],
        );

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
