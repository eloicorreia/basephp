<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Auth\PermissionRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'code',
        'name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<TenantUser, $this>
     */
    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withPivot(['assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->active) {
            return false;
        }

        return $this->permissions()
            ->where('permissions.active', true)
            ->whereIn('permissions.code', [$permission, PermissionRegistry::ADMIN_FULL])
            ->exists();
    }
}
