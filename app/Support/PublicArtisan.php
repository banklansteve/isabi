<?php

namespace App\Support;

use App\Models\ProfileSlugRedirect;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

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
}
