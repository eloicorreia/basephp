<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'group',
        'context',
        'is_system',
        'is_sensitive',
        'active',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_sensitive' => 'boolean',
        'active' => 'boolean',
    ];

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withPivot(['assigned_by', 'assigned_at'])
            ->withTimestamps();
    }
}
