<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\Auth\EmailVerificationService;
use App\Support\JobCategories;
use App\Support\NigeriaLocations;
use App\Support\ProfileSlug;
use App\Support\Referrals\ReferralService;
use App\Support\SkillsCatalog;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view (or same-screen verify step for unverified users).
     */
    public function create(Request $request, EmailVerificationService $verification): RedirectResponse|Response
    {
        $user = $request->user();

        if ($user?->hasVerifiedEmail()) {
            return redirect()->route($user->homeRouteName());
        }

        if ($user && ! $user->isStaff()) {
            // Only re-issue when there is no usable code left — never on every visit.
            if (
                blank($user->email_verification_code_hash)
                || blank($user->email_verification_code_expires_at)
                || $user->email_verification_code_expires_at->isPast()
            ) {
                try {
                    $verification->assertCanResend($user);
                    $verification->issue($user, $request->session()->getId());
                } catch (\Illuminate\Validation\ValidationException) {
                    // Cooldown — still show the verify UI with remaining wait.
                }
            }

            return $this->registerPage(
                pendingVerification: [
                    'email' => $user->email,
                    'resendCooldown' => $verification->secondsUntilResend($user->fresh()),
                    'codeTtlMinutes' => EmailVerificationService::CODE_TTL_MINUTES,
                ],
                status: session('status'),
            );
        }

        if ($user?->isStaff()) {
            return redirect()->route($user->homeRouteName());
        }

        $ref = trim((string) $request->query('ref', ''));

        return $this->registerPage(
            referralCode: $ref !== '' ? $ref : null,
        );
    }

    /**
     * Create the account, sign them in, email a one-time code, and keep them
     * on the same signup screen to enter it (soft-gate — they may continue later).
     */
    public function store(
        RegisterRequest $request,
        ReferralService $referrals,
        EmailVerificationService $verification,
    ): Response {
        // Purge any leftover session / remember-me from a previous account so the
        // new signup owns this browser context (avoids logging into the old account).
        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->forget('url.intended');
        }

        $data = $request->validated();
        $slug = ProfileSlug::uniqueFrom($data['business_name']);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'business_name' => $data['business_name'],
            'slug' => $slug,
            'email' => $data['email'],
            'trade' => $data['trade'],
            'trades' => $data['trades'],
            'skills' => $data['skills'] ?? [],
            'state' => $data['state'],
            'lga' => $data['lga'],
            'office_address' => $data['office_address'],
            'whatsapp' => $data['whatsapp'],
            'password' => $data['password'],
            'terms_accepted_at' => now(),
            'role' => UserRole::User,
        ]);

        $referrals->attributeOnSignup($user, $data['ref'] ?? null);

        Auth::login($user);
        $request->session()->forget('url.intended');

        // Single code send — Registered listener must not send a second mail.
        $verification->issue($user, $request->session()->getId());
        event(new Registered($user));

        ActivityLogger::log(
            action: 'auth.register',
            summary: "{$user->name} created a Kraftrack account as a {$user->trade}.",
            user: $user,
            properties: [
                'trade' => $user->trade,
                'trades' => $user->trades,
                'skills' => $user->skills,
                'state' => $user->state,
                'lga' => $user->lga,
                'ref' => $data['ref'] ?? null,
            ],
        );

        return $this->registerPage(
            pendingVerification: [
                'email' => $user->email,
                'resendCooldown' => EmailVerificationService::RESEND_COOLDOWN_SECONDS,
                'codeTtlMinutes' => EmailVerificationService::CODE_TTL_MINUTES,
            ],
        );
    }

    /**
     * @param  array{email: string, resendCooldown: int, codeTtlMinutes: int}|null  $pendingVerification
     */
    private function registerPage(
        ?array $pendingVerification = null,
        ?string $referralCode = null,
        mixed $status = null,
    ): Response {
        return Inertia::render('Auth/Register', [
            'trades' => JobCategories::tradeLabels(),
            'jobCategories' => JobCategories::forFrontend(),
            'skillCatalog' => SkillsCatalog::forFrontend(),
            'locations' => NigeriaLocations::all(),
            'referralCode' => $referralCode,
            'pendingVerification' => $pendingVerification,
            'status' => $status,
        ]);
    }
}
