<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompensationRecord extends Model
{
    protected $fillable = [
        'user_id',
        'currency',
        'base_salary',
        'pay_frequency',
        'effective_from',
        'note',
        'reason',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'effective_from' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function allowances(): HasMany
    {
        return $this->hasMany(CompensationAllowance::class)->orderBy('sort_order');
    }

    /**
     * The package in force today — latest effective date that is not in the future.
     */
    public static function currentFor(User $user): ?self
    {
        $today = now()->toDateString();

        return static::query()
            ->with('allowances')
            ->where('user_id', $user->id)
            ->where(function ($query) use ($today) {
                $query->whereNull('effective_from')
                    ->orWhereDate('effective_from', '<=', $today);
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    public function allowancesTotal(): float
    {
        return (float) $this->allowances->sum('amount');
    }

    public function grossTotal(): float
    {
        return (float) $this->base_salary + $this->allowancesTotal();
    }
}
