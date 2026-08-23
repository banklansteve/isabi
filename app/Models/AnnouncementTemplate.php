<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'audience',
    'name',
    'slug',
    'subject',
    'body',
    'channels',
    'is_system',
    'created_by_user_id',
])]
class AnnouncementTemplate extends Model
{
    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'is_system' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }
}
