<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrProfile extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXITED = 'exited';

    protected $fillable = [
        'user_id',
        'position',
        'department',
        'employment_type',
        'employment_status',
        'start_date',
        'exit_date',
        'exit_reason',
        'personal_email',
        'personal_phone',
        'home_address',
        'date_of_birth',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'exit_date' => 'date',
            'date_of_birth' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExited(): bool
    {
        return $this->employment_status === self::STATUS_EXITED;
    }
}
