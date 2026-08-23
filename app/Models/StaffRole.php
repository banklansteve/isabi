<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug',
    'name',
    'description',
    'icon',
    'permissions',
    'is_system',
    'is_active',
    'sort_order',
    'created_by_user_id',
])]
class StaffRole extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'permissions' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(StaffRoleAssignment::class);
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'staff_role_assignments')
            ->withTimestamps()
            ->withPivot(['assigned_by_user_id', 'assigned_at']);
    }

    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'is_system' => $this->is_system,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'permissions' => array_values($this->permissions ?? []),
            'permissions_count' => count($this->permissions ?? []),
            'assigned_count' => $this->assignments_count ?? $this->assignments()->count(),
        ];
    }
}
