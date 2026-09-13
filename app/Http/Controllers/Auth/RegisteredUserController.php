<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\JobCategories;
use App\Support\NigeriaLocations;
use App\Support\ProfileSlug;
use App\Support\Referrals\ReferralService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): Response
    {
        $ref = trim((string) $request->query('ref', ''));

        return Inertia::render('Auth/Register', [
            'trades' => JobCategories::tradeLabels(),
            'jobCategories' => JobCategories::forFrontend(),
            'locations' => NigeriaLocations::all(),
            'referralCode' => $ref !== '' ? $ref : null,
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterRequest $request, ReferralService $referrals): RedirectResponse
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

        $referrals->attributeOnSignup($user, $data['ref'] ?? null);

        event(new Registered($user));

        Auth::login($user);

        ActivityLogger::log(
            action: 'auth.register',
            summary: "{$user->name} created a Kraftrack account as a {$user->trade}.",
            user: $user,
            properties: [
                'trade' => $user->trade,
                'state' => $user->state,
                'lga' => $user->lga,
                'ref' => $data['ref'] ?? null,
            ],
        );

        return redirect(route($user->homeRouteName(), absolute: false));
    }
}
