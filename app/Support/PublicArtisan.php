<?php

namespace App\Support;

use App\Models\ProfileSlugRedirect;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class PublicArtisan
{
    public static function locate(string $slug): User|RedirectResponse
    {
        $user = User::query()->artisans()->where('slug', $slug)->first();

        if ($user) {
            return $user;
        }

        $redirect = ProfileSlugRedirect::query()
            ->where('from_slug', $slug)
            ->with('user')
            ->first();

        if ($redirect?->user?->slug) {
            return redirect()->to('/p/'.$redirect->user->slug, 301);
        }

        abort(404);
    }

    /**
     * When the page is off, public visitors get a 404 Unavailable screen.
     * Owner and staff may still preview.
     */
    public static function denyUnlessVisible(User $artisan, ?User $viewer = null): ?Response
    {
        if ($artisan->isPublicPageEnabled()) {
            return null;
        }

        $viewerIsOwner = $viewer !== null && (int) $viewer->id === (int) $artisan->id;
        $viewerIsStaff = $viewer !== null && $viewer->isStaff();

        if ($viewerIsOwner || $viewerIsStaff) {
            return null;
        }

        return Inertia::render('Public/Unavailable', [
            'message' => 'This artisan’s public page is turned off right now.',
        ])->toResponse(request())->setStatusCode(Response::HTTP_NOT_FOUND);
    }
}
