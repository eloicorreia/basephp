<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoleCode;
use App\Support\Auth\PermissionRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected static function booted(): void
    {
        static::created(function (Role $role): void {
            if ($role->code !== RoleCode::ADMIN->value) {
                return;
            }

            foreach (PermissionRegistry::definitions() as $code => $definition) {
                Permission::query()->updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $definition['name'],
                        'description' => $definition['description'],
                        'context' => $definition['context'],
                        'is_sensitive' => $definition['is_sensitive'],
                        'active' => true,
                    ]
                );
            }

            $permissionIds = Permission::query()
                ->whereIn('code', PermissionRegistry::codes())
                ->pluck('id');

            if ($permissionIds->isNotEmpty()) {
                $role->permissions()->syncWithoutDetaching($permissionIds->all());
            }
        });
    }

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
