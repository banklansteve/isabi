<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\NigeriaLocations;
use App\Support\ProfileSlug;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'trades' => config('trades'),
            'locations' => NigeriaLocations::all(),
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $slug = ProfileSlug::uniqueFrom($data['business_name']);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'business_name' => $data['business_name'],
            'slug' => $slug,
            'email' => $data['email'],
            'trade' => $data['trade'],
            'state' => $data['state'],
            'lga' => $data['lga'],
            'office_address' => $data['office_address'],
            'whatsapp' => $data['whatsapp'],
            'password' => $data['password'],
            'role' => UserRole::User,
        ]);

        event(new Registered($user));

        Auth::login($user);

        ActivityLogger::log(
            action: 'auth.register',
            summary: "{$user->name} created an Isabi account as a {$user->trade}.",
            user: $user,
            properties: [
                'trade' => $user->trade,
                'state' => $user->state,
                'lga' => $user->lga,
            ],
        );

        return redirect(route($user->homeRouteName(), absolute: false));
    }
}
