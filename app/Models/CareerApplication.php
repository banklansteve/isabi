<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CareerApplication extends Model
{
    protected $fillable = [
        'career_vacancy_id',
        'public_uid',
        'status',
        'full_name',
        'email',
        'phone',
        'city',
        'linkedin_url',
        'portfolio_url',
        'education',
        'certifications',
        'secondary_education',
        'primary_education',
        'no_work_experience',
        'work_experience',
        'skills',
        'skills_other',
        'achievements',
        'nysc_status',
        'willing_to_relocate',
        'preferred_work_mode',
        'earliest_availability',
        'expected_salary',
        'notice_period',
        'why_this_role',
        'cv_disk',
        'cv_path',
        'cv_url',
        'cv_name',
        'work_sample_url',
        'references',
        'ndpr_consent',
        'ndpr_consented_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'education' => 'array',
            'work_experience' => 'array',
            'skills' => 'array',
            'references' => 'array',
            'no_work_experience' => 'boolean',
            'willing_to_relocate' => 'boolean',
            'ndpr_consent' => 'boolean',
            'ndpr_consented_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $application): void {
            if (! $application->public_uid) {
                $application->public_uid = Str::lower(Str::random(16));
            }
        });
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(CareerVacancy::class, 'career_vacancy_id');
    }
}
