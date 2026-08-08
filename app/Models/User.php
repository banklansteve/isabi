<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'first_name',
    'last_name',
    'business_name',
    'slug',
    'slug_change_count',
    'slug_changed_at',
    'email',
    'password',
    'role',
    'trade',
    'state',
    'lga',
    'office_address',
    'whatsapp',
    'bio',
    'avatar_path',
    'avatar_url',
    'profile_completion',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'profile_completion' => 'integer',
            'slug_change_count' => 'integer',
            'slug_changed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if ($user->role === null) {
                $user->role = UserRole::User;
            }
        });

        static::saving(function (User $user): void {
            if ($user->first_name || $user->last_name) {
                $user->name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
            }

            $user->profile_completion = $user->calculateProfileCompletion();
        });
    }

    public function calculateProfileCompletion(): int
    {
        $fields = [
            'first_name',
            'last_name',
            'business_name',
            'slug',
            'email',
            'trade',
            'state',
            'lga',
            'office_address',
            'whatsapp',
        ];

        $filled = collect($fields)
            ->filter(fn (string $field) => filled($this->{$field}))
            ->count();

        // Signup fields are ~50%; remaining room is for photo, bio, and reviews.
        $signupWeight = 50;
        $base = (int) round(($filled / count($fields)) * $signupWeight);

        if (filled($this->bio)) {
            $base += 15;
        }
        if (filled($this->avatar_url) || filled($this->avatar_path)) {
            $base += 20;
        }

        return min(100, $base);
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

    public function canDo(string $ability): bool
    {
        return $this->role?->can($ability) ?? false;
    }

    public function homeRouteName(): string
    {
        return $this->role?->homeRouteName() ?? 'dashboard';
    }

    public function workLogs(): HasMany
    {
        return $this->hasMany(WorkLog::class);
    }
}

