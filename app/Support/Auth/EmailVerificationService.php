<?php

namespace App\Support\Auth;

use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmailVerificationService
{
    public const CODE_TTL_MINUTES = 15;

    public const LINK_TTL_MINUTES = 15;

    public const MAX_ATTEMPTS = 5;

    public const RESEND_COOLDOWN_SECONDS = 45;

    /**
     * Issue a fresh 6-digit code + one-click link and email them.
     *
     * @return array{code: string, verify_url: string, cooldown: int}
     */
    public function issue(User $user, ?string $sessionId = null, bool $sendMail = true): array
    {
        $code = (string) random_int(100000, 999999);
        $linkToken = Str::random(64);
        $sessionId = $sessionId ?: (string) Str::uuid();

        $user->forceFill([
            'email_verification_code_hash' => Hash::make($code),
            'email_verification_code_expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
            'email_verification_attempts' => 0,
            'email_verification_sent_at' => now(),
            'email_verification_link_hash' => hash('sha256', $linkToken),
            'email_verification_link_expires_at' => now()->addMinutes(self::LINK_TTL_MINUTES),
            'email_verification_session_id' => $sessionId,
        ])->save();

        $verifyUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(self::LINK_TTL_MINUTES),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
                'token' => $linkToken,
            ],
        );

        // Prefer absolute app URL for email clients.
        if (! str_starts_with($verifyUrl, 'http')) {
            $verifyUrl = url($verifyUrl);
        }

        if ($sendMail) {
            Mail::to($user->email)->send(new VerifyEmailMail($user, $code, $verifyUrl));
        }

        return [
            'code' => $code,
            'verify_url' => $verifyUrl,
            'cooldown' => self::RESEND_COOLDOWN_SECONDS,
        ];
    }

    public function assertCanResend(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $sentAt = $user->email_verification_sent_at;
        if ($sentAt && $sentAt->gt(now()->subSeconds(self::RESEND_COOLDOWN_SECONDS))) {
            $wait = self::RESEND_COOLDOWN_SECONDS - $sentAt->diffInSeconds(now());
            throw ValidationException::withMessages([
                'email' => "Please wait {$wait} seconds before requesting another code.",
            ]);
        }
    }

    public function secondsUntilResend(User $user): int
    {
        $sentAt = $user->email_verification_sent_at;
        if (! $sentAt) {
            return 0;
        }

        $elapsed = $sentAt->diffInSeconds(now());
        $remaining = self::RESEND_COOLDOWN_SECONDS - $elapsed;

        return max(0, (int) $remaining);
    }

    /**
     * @throws ValidationException
     */
    public function verifyCode(User $user, string $code): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (! preg_match('/^\d{6}$/', $code)) {
            throw ValidationException::withMessages([
                'code' => 'Enter the 6-digit code from your email.',
            ]);
        }

        if ($user->email_verification_attempts >= self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages([
                'code' => 'Too many attempts. Request a new code to continue.',
            ]);
        }

        if (
            blank($user->email_verification_code_hash)
            || blank($user->email_verification_code_expires_at)
            || $user->email_verification_code_expires_at->isPast()
        ) {
            $user->increment('email_verification_attempts');
            throw ValidationException::withMessages([
                'code' => 'That code has expired. Request a new one.',
            ]);
        }

        if (! Hash::check($code, $user->email_verification_code_hash)) {
            $user->increment('email_verification_attempts');
            $remaining = self::MAX_ATTEMPTS - (int) $user->fresh()->email_verification_attempts;
            throw ValidationException::withMessages([
                'code' => $remaining > 0
                    ? "That code doesn’t match. {$remaining} tries left."
                    : 'Too many attempts. Request a new code to continue.',
            ]);
        }

        $this->markVerified($user);
    }

    /**
     * Verify via one-click link token (single-use).
     *
     * @return array{verified: bool, same_session: bool}
     */
    public function verifyLink(User $user, string $token, ?string $currentSessionId = null): array
    {
        if ($user->hasVerifiedEmail()) {
            return [
                'verified' => true,
                'same_session' => $this->isSameSession($user, $currentSessionId),
            ];
        }

        if (
            blank($user->email_verification_link_hash)
            || blank($user->email_verification_link_expires_at)
            || $user->email_verification_link_expires_at->isPast()
            || ! hash_equals($user->email_verification_link_hash, hash('sha256', $token))
        ) {
            throw ValidationException::withMessages([
                'link' => 'This verification link is invalid or has expired. Enter the code from your email instead.',
            ]);
        }

        $sameSession = $this->isSameSession($user, $currentSessionId);
        $this->markVerified($user);

        return [
            'verified' => true,
            'same_session' => $sameSession,
        ];
    }

    public function markVerified(User $user): void
    {
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        $this->clearChallenge($user);
    }

    public function clearChallenge(User $user): void
    {
        $user->forceFill([
            'email_verification_code_hash' => null,
            'email_verification_code_expires_at' => null,
            'email_verification_attempts' => 0,
            'email_verification_link_hash' => null,
            'email_verification_link_expires_at' => null,
            'email_verification_session_id' => null,
        ])->save();
    }

    private function isSameSession(User $user, ?string $currentSessionId): bool
    {
        if (blank($user->email_verification_session_id) || blank($currentSessionId)) {
            return false;
        }

        return hash_equals((string) $user->email_verification_session_id, (string) $currentSessionId);
    }
}
