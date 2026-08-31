<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\StaffStatus;
use App\Enums\UserRole;
use App\Notifications\StaffResetPasswordNotification;
use App\Support\Identity\UserUid;
use App\Support\Referrals\ReferralService;
use App\Support\Staff\AdminPermissions;
use Database\Factories\UserFactory;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'first_name',
    'last_name',
    'business_name',
    'slug',
    'slug_change_count',
    'slug_changed_at',
    'referral_code',
    'referred_by_user_id',
    'referred_at',
    'email',
    'password',
    'password_set_at',
    'role',
    'staff_status',
    'invited_by_user_id',
    'suspended_at',
    'suspension_reason',
    'trade',
    'skills',
    'credentials',
    'experience_started_year',
    'state',
    'lga',
    'coverage_areas',
    'coverage_note',
    'office_address',
    'whatsapp',
    'bio',
    'review_invite_template',
    'review_reminder_template',
    'review_reminder_days',
    'avatar_path',
    'avatar_url',
    'logo_path',
    'logo_url',
    'profile_completion',
    'token_balance',
    'plan',
    'annual_expires_at',
    'public_page_views',
    'last_login_ip',
    'last_login_at',
    'last_logout_at',
    'last_seen_at',
    'shift_days',
    'shift_starts_at',
    'shift_ends_at',
    'shift_breaks',
    'session_epoch',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_set_at' => 'datetime',
            'role' => UserRole::class,
            'staff_status' => StaffStatus::class,
            'suspended_at' => 'datetime',
            'profile_completion' => 'integer',
            'slug_change_count' => 'integer',
            'slug_changed_at' => 'datetime',
            'referred_by_user_id' => 'integer',
            'referred_at' => 'datetime',
            'skills' => 'array',
            'credentials' => 'array',
            'coverage_areas' => 'array',
            'experience_started_year' => 'integer',
            'review_reminder_days' => 'integer',
            'token_balance' => 'integer',
            'annual_expires_at' => 'datetime',
            'public_page_views' => 'integer',
            'last_login_at' => 'datetime',
            'last_logout_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'shift_days' => 'array',
            'shift_breaks' => 'array',
            'session_epoch' => 'integer',
        ];
    }

    public function reviewInviteTemplate(): string
    {
        return filled($this->review_invite_template)
            ? (string) $this->review_invite_template
            : (string) config('review_messages.invite');
    }

    public function reviewReminderTemplate(): string
    {
        return filled($this->review_reminder_template)
            ? (string) $this->review_reminder_template
            : (string) config('review_messages.reminder');
    }

    /** Effective wait (in days) before a reminder is due. Null config → default; 0 → off. */
    public function reviewReminderDays(): ?int
    {
        if ($this->review_reminder_days === null) {
            $default = (int) config('review_messages.default_reminder_days', 3);

            return $default > 0 ? $default : null;
        }

        $days = (int) $this->review_reminder_days;

        return $days > 0 ? $days : null;
    }

    /** Whether this artisan wants automatic review nudges prepared. */
    public function wantsReviewReminders(): bool
    {
        return $this->reviewReminderDays() !== null;
    }

    /**
     * Whole years since the artisan says they started working, or null when
     * they haven't told us. Never negative, even if the year is mistyped.
     */
    public function yearsActive(): ?int
    {
        $started = $this->experience_started_year;

        if (! $started) {
            return null;
        }

        return max(0, (int) now()->year - (int) $started);
    }

    /**
     * Free-text line plus named areas, e.g. "Ikeja, Alimosho · Same-day within Lagos".
     */
    public function coverageSummary(): ?string
    {
        $areas = collect($this->coverage_areas ?? [])->filter()->implode(', ');
        $parts = collect([$areas ?: null, $this->coverage_note ?: null])->filter();

        return $parts->isNotEmpty() ? $parts->implode(' · ') : null;
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if ($user->role === null) {
                $user->role = UserRole::User;
            }

            if ($user->role->isStaff()) {
                $user->hydrateStaffDefaults();
            }

            if (blank($user->referral_code) && $user->isRegularUser()) {
                $user->referral_code = ReferralService::generateCode();
            }

            UserUid::fill($user);
        });

        static::saving(function (User $user): void {
            UserUid::fill($user);

            if ($user->first_name || $user->last_name) {
                $user->name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
            }

            if ($user->isStaff()) {
                $user->profile_completion = 0;

                return;
            }

            $user->profile_completion = $user->calculateProfileCompletion();
        });
    }

    /**
     * Weighted toward what clients actually notice on the public page.
     * Checklist items: WhatsApp, service area, bio, photo (+ light essentials floor).
     */
    public function calculateProfileCompletion(): int
    {
        $score = 0;

        // Essentials from signup — quiet floor, not checklist clutter.
        $essentials = [
            filled($this->first_name) && filled($this->last_name),
            filled($this->business_name) && filled($this->slug),
            filled($this->trade),
            filled($this->email),
        ];
        $essentialsFilled = collect($essentials)->filter()->count();
        $score += (int) round(($essentialsFilled / max(1, count($essentials))) * 20);

        if (filled($this->whatsapp)) {
            $score += 20;
        }

        $hasServiceArea = (filled($this->state) && filled($this->lga))
            || (is_array($this->coverage_areas) && count($this->coverage_areas) > 0);
        if ($hasServiceArea) {
            $score += 20;
        }

        if (filled($this->bio)) {
            $score += 20;
        }

        if (filled($this->avatar_url) || filled($this->avatar_path)) {
            $score += 20;
        }

        return min(100, $score);
    }

    /**
     * Missing checklist items that materially improve the public page.
     *
     * @return list<array{key: string, label: string, detail: string, href: string}>
     */
    public function profileCompletionChecklist(): array
    {
        $items = [];

        if (! filled($this->avatar_url) && ! filled($this->avatar_path)) {
            $items[] = [
                'key' => 'photo',
                'label' => 'Add a profile photo',
                'detail' => 'A real face builds trust faster than initials.',
                'href' => route('profile.edit').'#section-profile',
            ];
        }

        if (! filled($this->bio)) {
            $items[] = [
                'key' => 'bio',
                'label' => 'Write a short bio',
                'detail' => 'One or two lines about the work you do.',
                'href' => route('profile.edit').'#section-profile',
            ];
        }

        $hasCoverage = is_array($this->coverage_areas) && count($this->coverage_areas) > 0;
        if (! $hasCoverage && (! filled($this->state) || ! filled($this->lga))) {
            $items[] = [
                'key' => 'area',
                'label' => 'Add your service area',
                'detail' => 'State, LGA, or the areas you usually cover.',
                'href' => route('profile.edit').'#section-profile',
            ];
        } elseif (! $hasCoverage) {
            $items[] = [
                'key' => 'area',
                'label' => 'Add areas you cover',
                'detail' => 'Help clients see if you reach their neighbourhood.',
                'href' => route('profile.edit').'#section-profile',
            ];
        }

        if (! filled($this->whatsapp)) {
            $items[] = [
                'key' => 'whatsapp',
                'label' => 'Add your WhatsApp number',
                'detail' => 'How new clients message you from your page.',
                'href' => route('profile.edit').'#section-profile',
            ];
        }

        return $items;
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_user_id');
    }

    public function referralsMade(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_user_id');
    }

    public function publicUrl(): ?string
    {
        if (blank($this->slug)) {
            return null;
        }

        return url('/p/'.$this->slug);
    }

    public function displayBusinessName(): string
    {
        return $this->business_name
            ?: trim(($this->first_name ?? '').' '.($this->last_name ?? ''))
            ?: (string) $this->name
            ?: 'Artisan';
    }

    /** Logo for PDFs, embeds, and exports — falls back to profile photo. */
    public function brandLogoUrl(): ?string
    {
        if (filled($this->logo_url)) {
            return $this->logo_url;
        }

        if (filled($this->avatar_url)) {
            return $this->avatar_url;
        }

        return null;
    }

    public function slugChangesRemaining(): int
    {
        $max = (int) config('profiles.max_slug_changes', 3);

        return max(0, $max - (int) $this->slug_change_count);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function slugRedirects(): HasMany
    {
        return $this->hasMany(ProfileSlugRedirect::class);
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isOperationsAdmin(): bool
    {
        return $this->role === UserRole::OperationsAdmin;
    }

    public function isStaff(): bool
    {
        return $this->role?->isStaff() ?? false;
    }

    public function isRegularUser(): bool
    {
        return $this->role === UserRole::User;
    }

    public function roleLabel(): string
    {
        return $this->role?->label() ?? 'User';
    }

    public function roleKey(): string
    {
        return $this->role?->value ?? UserRole::User->value;
    }

    public function canDo(string $ability): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->isStaff() || $this->isSuspended()) {
            return false;
        }

        if ($ability === AdminPermissions::ACCESS) {
            return true;
        }

        return in_array($ability, $this->permissionKeys(), true);
    }

    /**
     * @return list<string>
     */
    public function permissionKeys(): array
    {
        if ($this->isSuperAdmin()) {
            return AdminPermissions::keys();
        }

        $this->loadMissing('staffRoles');

        return $this->staffRoles
            ->filter(fn (StaffRole $role) => $role->is_active)
            ->flatMap(fn (StaffRole $role) => $role->permissions ?? [])
            ->unique()
            ->values()
            ->all();
    }

    public function isRestrictedStaff(): bool
    {
        return $this->isOperationsAdmin()
            && ! $this->isSuspended()
            && $this->permissionKeys() === [];
    }

    public function invalidateSessions(): void
    {
        $this->forceFill([
            'remember_token' => Str::random(60),
            'session_epoch' => ((int) $this->session_epoch) + 1,
        ])->save();

        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $this->id)
            ->delete();
    }

    public static function activeSuperAdminCount(): int
    {
        return static::query()
            ->where('role', UserRole::SuperAdmin)
            ->where('staff_status', '!=', StaffStatus::Suspended)
            ->whereNull('suspended_at')
            ->count();
    }

    public function homeRouteName(): string
    {
        return $this->role?->homeRouteName() ?? 'dashboard';
    }

    public function workLogs(): HasMany
    {
        return $this->hasMany(WorkLog::class);
    }

    public function patrolCases(): HasMany
    {
        return $this->hasMany(PatrolCase::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function tokenTransactions(): HasMany
    {
        return $this->hasMany(TokenTransaction::class);
    }

    public function tokenPurchases(): HasMany
    {
        return $this->hasMany(TokenPurchase::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'invited_by_user_id');
    }

    public function staffInvitations(): HasMany
    {
        return $this->hasMany(StaffInvitation::class);
    }

    public function latestStaffInvitation(): HasOne
    {
        return $this->hasOne(StaffInvitation::class)->latestOfMany();
    }

    public function staffRoleAssignments(): HasMany
    {
        return $this->hasMany(StaffRoleAssignment::class);
    }

    public function staffRoles(): BelongsToMany
    {
        return $this->belongsToMany(StaffRole::class, 'staff_role_assignments')
            ->withTimestamps()
            ->withPivot(['assigned_by_user_id', 'assigned_at']);
    }

    public function announcementDeliveries(): HasMany
    {
        return $this->hasMany(AnnouncementDelivery::class);
    }

    public function scopeArtisans(Builder $query): Builder
    {
        return $query->where('role', UserRole::User);
    }

    public function scopeStaff(Builder $query): Builder
    {
        return $query->whereIn('role', [
            UserRole::SuperAdmin,
            UserRole::OperationsAdmin,
        ]);
    }

    public function hasSetPassword(): bool
    {
        return filled($this->password) && $this->password_set_at !== null;
    }

    public function isSuspended(): bool
    {
        return $this->staff_status === StaffStatus::Suspended || $this->suspended_at !== null;
    }

    public function hasDuty(string $slug): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->isOperationsAdmin()) {
            return false;
        }

        return $this->staffRoles->contains(
            fn (StaffRole $role) => $role->slug === $slug && $role->is_active
        );
    }

    /**
     * @return list<array{id: int, slug: string, name: string, icon: string|null, description: string|null}>
     */
    public function assignedDuties(): array
    {
        if ($this->isSuperAdmin()) {
            return StaffRole::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (StaffRole $role) => [
                    'id' => $role->id,
                    'slug' => $role->slug,
                    'name' => $role->name,
                    'icon' => $role->icon,
                    'description' => $role->description,
                ])
                ->all();
        }

        return $this->staffRoles
            ->filter(fn (StaffRole $role) => $role->is_active)
            ->sortBy('sort_order')
            ->values()
            ->map(fn (StaffRole $role) => [
                'id' => $role->id,
                'slug' => $role->slug,
                'name' => $role->name,
                'icon' => $role->icon,
                'description' => $role->description,
            ])
            ->all();
    }

    public function sendPasswordResetNotification($token): void
    {
        if ($this->isStaff()) {
            $this->notify(new StaffResetPasswordNotification($token));

            return;
        }

        $this->notify(new ResetPassword($token));
    }

    /**
     * Create a fully active super admin — intended for Tinker / artisan.
     */
    public static function createSuperAdmin(
        string $email,
        string $password,
        string $firstName,
        string $lastName,
    ): self {
        return self::query()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => $password,
            'role' => UserRole::SuperAdmin,
            'staff_status' => StaffStatus::Active,
            'email_verified_at' => now(),
            'password_set_at' => now(),
        ]);
    }

    protected function hydrateStaffDefaults(): void
    {
        if ($this->staff_status === null) {
            $this->staff_status = filled($this->password)
                ? StaffStatus::Active
                : StaffStatus::Invited;
        }

        if (filled($this->password) && $this->password_set_at === null) {
            $this->password_set_at = now();
        }

        if ($this->role === UserRole::SuperAdmin && $this->email_verified_at === null && filled($this->password)) {
            $this->email_verified_at = now();
        }
    }

    public function hrProfile(): HasOne
    {
        return $this->hasOne(HrProfile::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveAllocations(): HasMany
    {
        return $this->hasMany(LeaveAllocation::class);
    }

    public function compensationRecords(): HasMany
    {
        return $this->hasMany(CompensationRecord::class);
    }

    public function compensationRecord(): HasOne
    {
        return $this->hasOne(CompensationRecord::class)->latestOfMany();
    }

    public function disciplinaryRecords(): HasMany
    {
        return $this->hasMany(DisciplinaryRecord::class);
    }

    public function disciplinaryCases(): HasMany
    {
        return $this->hasMany(DisciplinaryCase::class);
    }

    public function pendingDisciplinaryNoticeCount(): int
    {
        return DisciplinaryAction::query()
            ->whereHas('case', fn ($query) => $query->where('user_id', $this->id))
            ->whereNull('acknowledged_at')
            ->count();
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function performanceNotes(): HasMany
    {
        return $this->hasMany(PerformanceNote::class);
    }

    public function staffDocuments(): HasMany
    {
        return $this->hasMany(StaffDocument::class);
    }

    public function checklistInstances(): HasMany
    {
        return $this->hasMany(ChecklistInstance::class);
    }

    /** Whether this staff member has an approved leave covering the given date (default today). */
    public function isOnLeaveOn(?Carbon $date = null): bool
    {
        $date = ($date ?? now())->toDateString();

        return $this->leaveRequests()
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();
    }
}
