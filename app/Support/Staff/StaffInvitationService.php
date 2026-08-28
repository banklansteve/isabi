<?php

namespace App\Support\Staff;

use App\Enums\StaffStatus;
use App\Enums\UserRole;
use App\Mail\StaffInvitationMail;
use App\Models\StaffInvitation;
use App\Models\StaffRole;
use App\Models\User;
use App\Support\Admin\AdminAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StaffInvitationService
{
    /**
     * @return array{user: User, invitation: StaffInvitation, token: string, expires_hours: int}
     */
    public function invite(
        User $invitedBy,
        string $email,
        ?string $name = null,
        ?int $suggestedRoleId = null,
    ): array {
        $email = strtolower(trim($email));
        $this->assertInvitable($email);

        [$firstName, $lastName] = $this->splitName($name);

        $payload = DB::transaction(function () use ($invitedBy, $email, $firstName, $lastName, $suggestedRoleId) {
            $user = User::query()->create([
                'first_name' => $firstName ?: null,
                'last_name' => $lastName ?: null,
                'name' => trim($firstName.' '.$lastName) ?: Str::before($email, '@'),
                'email' => $email,
                'password' => null,
                'role' => UserRole::OperationsAdmin,
                'staff_status' => StaffStatus::Invited,
                'invited_by_user_id' => $invitedBy->id,
                'email_verified_at' => null,
                'password_set_at' => null,
            ]);

            $payload = $this->issueToken($user, $invitedBy, $suggestedRoleId, send: false);

            AdminAudit::record(
                'staff.invited',
                "{$invitedBy->name} invited {$email} to operations.",
                $user,
                null,
                [
                    'email' => $email,
                    'suggested_staff_role_id' => $suggestedRoleId,
                    'reason' => 'Staff invitation',
                ],
                $invitedBy,
            );

            return $payload;
        });

        $this->deliverInvite($payload['user'], $payload['token'], $payload['expires_hours'], $invitedBy);

        return $payload;
    }

    /**
     * @return array{user: User, invitation: StaffInvitation, token: string, expires_hours: int}
     */
    public function resend(User $staff, User $actor): array
    {
        abort_unless($staff->isOperationsAdmin() && ! $staff->hasSetPassword(), 422, 'Only pending invites can be resent.');

        $invitation = $staff->latestStaffInvitation;

        if ($invitation && ! $invitation->canResend()) {
            $seconds = $invitation->secondsUntilResend();

            throw ValidationException::withMessages([
                'email' => "Wait {$seconds} seconds before sending another invite.",
            ]);
        }

        $payload = $this->issueToken(
            $staff,
            $actor,
            $invitation?->suggested_staff_role_id,
        );

        AdminAudit::record(
            'staff.invite_resent',
            "{$actor->name} resent the invite to {$staff->email}.",
            $staff,
            null,
            ['email' => $staff->email],
            $actor,
        );

        return $payload;
    }

    public function revoke(User $staff, User $actor, string $reason): void
    {
        abort_unless($staff->isOperationsAdmin() && ! $staff->hasSetPassword(), 422, 'Only pending invites can be revoked.');

        DB::transaction(function () use ($staff, $actor, $reason) {
            $staff->latestStaffInvitation?->forceFill([
                'revoked_at' => now(),
                'consumed_at' => now(),
                'token_hash' => null,
            ])->save();

            AdminAudit::record(
                'staff.invite_revoked',
                "{$actor->name} revoked the invite for {$staff->email}: {$reason}",
                $staff,
                ['staff_status' => $staff->staff_status?->value],
                ['reason' => $reason],
                $actor,
            );

            $staff->forceDelete();
        });
    }

    /**
     * @return array{user: User, invitation: StaffInvitation, token: string, expires_hours: int}
     */
    public function issueToken(User $user, ?User $invitedBy = null, ?int $suggestedRoleId = null, bool $send = true): array
    {
        $hours = AdminPermissions::ttlHours();
        $token = Str::random(64);

        StaffInvitation::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now(), 'token_hash' => null]);

        $invitation = StaffInvitation::query()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'token_hash' => hash('sha256', $token),
            'code_hash' => null,
            'attempts' => 0,
            'expires_at' => now()->addHours($hours),
            'last_sent_at' => now(),
            'verified_at' => null,
            'consumed_at' => null,
            'revoked_at' => null,
            'suggested_staff_role_id' => $suggestedRoleId,
            'invited_by_user_id' => $invitedBy?->id ?? $user->invited_by_user_id,
        ]);

        $payload = [
            'user' => $user->fresh(),
            'invitation' => $invitation->fresh(),
            'token' => $token,
            'expires_hours' => $hours,
        ];

        if ($send) {
            $this->deliverInvite($payload['user'], $token, $hours, $invitedBy);
        }

        return $payload;
    }

    private function deliverInvite(User $user, string $token, int $hours, ?User $invitedBy): void
    {
        try {
            Mail::to($user->email)->send(new StaffInvitationMail(
                invitee: $user,
                acceptUrl: route('admin.invite.accept', ['token' => $token], absolute: true),
                expiresHours: $hours,
                invitedBy: $invitedBy,
            ));
        } catch (\Throwable $e) {
            report($e);

            throw ValidationException::withMessages([
                'email' => $this->inviteMailError($e),
            ]);
        }
    }

    private function inviteMailError(\Throwable $e): string
    {
        $message = strtolower($e->getMessage());

        if (str_contains($message, 'failed to authenticate') || str_contains($message, '535')) {
            return 'Gmail rejected the SMTP username or app password. Create a new App Password and update MAIL_PASSWORD.';
        }

        if (str_contains($message, '550') || str_contains($message, 'from address')) {
            return 'Gmail rejected the From address. It must be the same Gmail account used for SMTP.';
        }

        if (str_contains($message, 'connection') || str_contains($message, 'timed out')) {
            return 'Could not reach smtp.gmail.com. Check the network and that port 587 is open.';
        }

        return 'The invite was saved, but the email could not be sent. Use Resend after checking SMTP settings.';
    }

    public function findPendingByToken(string $token): ?StaffInvitation
    {
        if ($token === '') {
            return null;
        }

        $invitation = StaffInvitation::query()
            ->with(['user', 'suggestedRole'])
            ->where('token_hash', hash('sha256', $token))
            ->latest('id')
            ->first();

        if (! $invitation || ! $invitation->isPending() || $invitation->isExpired()) {
            return null;
        }

        $user = $invitation->user;

        if (! $user?->isStaff() || $user->hasSetPassword()) {
            return null;
        }

        return $invitation;
    }

    public function accept(StaffInvitation $invitation, string $password, string $firstName, string $lastName): User
    {
        $user = $invitation->user;
        abort_unless($user && $invitation->isPending() && ! $invitation->isExpired(), 422);

        $user->first_name = $firstName;
        $user->last_name = $lastName;

        $user->forceFill([
            'password' => $password,
            'password_set_at' => now(),
            'staff_status' => StaffStatus::Active,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $invitation->forceFill([
            'verified_at' => $invitation->verified_at ?? now(),
            'consumed_at' => now(),
            'token_hash' => null,
        ])->save();

        if ($invitation->suggested_staff_role_id) {
            $role = StaffRole::query()
                ->whereKey($invitation->suggested_staff_role_id)
                ->where('is_active', true)
                ->first();

            if ($role) {
                app(StaffAssignmentService::class)->attach($user, $role, $invitation->invitedBy ?? $user);
            }
        }

        AdminAudit::record(
            'staff.invite_accepted',
            "{$user->email} accepted their operations invite.",
            $user,
            ['staff_status' => StaffStatus::Invited->value],
            ['staff_status' => StaffStatus::Active->value],
            $user,
        );

        return $user->fresh(['staffRoles']);
    }

    private function assertInvitable(string $email): void
    {
        $existing = User::query()->withTrashed()->where('email', $email)->first();

        if (! $existing) {
            return;
        }

        if ($existing->trashed()) {
            throw ValidationException::withMessages([
                'email' => 'That email belongs to a removed account. Restore it first, or use a different address.',
            ]);
        }

        if ($existing->isRegularUser()) {
            throw ValidationException::withMessages([
                'email' => 'That email is already an artisan account. Use a different work email.',
            ]);
        }

        if ($existing->isStaff() && $existing->hasSetPassword()) {
            throw ValidationException::withMessages([
                'email' => 'That person is already on the operations team.',
            ]);
        }

        if ($existing->isStaff() && ! $existing->hasSetPassword()) {
            throw ValidationException::withMessages([
                'email' => 'That email already has a pending invite. Resend it from the staff list.',
            ]);
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(?string $name): array
    {
        $name = trim((string) $name);

        if ($name === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', $name, 2) ?: [];

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }
}
