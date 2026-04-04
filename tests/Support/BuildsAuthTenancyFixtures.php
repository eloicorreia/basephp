<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Support\Str;

trait BuildsAuthTenancyFixtures
{
    private function createRole(string $code, string $name, bool $active = true): Role
    {
        return Role::query()->firstOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'active' => $active,
            ]
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createTenant(
        ?string $code = null,
        ?string $name = null,
        bool $isActive = true,
        ?string $schemaName = null,
        ?string $status = null,
        array $overrides = []
    ): Tenant {
        $resolvedStatus = $status ?? ($isActive ? 'active' : 'inactive');

        $attributes = array_merge([
            'uuid' => (string) Str::uuid(),
            'code' => $code ?? 'tenant-' . str_replace('-', '', (string) Str::uuid()),
            'name' => $name ?? 'Tenant ' . uniqid(),
            'schema_name' => $schemaName ?? 'tenant_' . uniqid(),
            'status' => $resolvedStatus,
        ], $overrides);

        return Tenant::query()->create($attributes);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createUser(
        ?Role $role = null,
        bool $isActive = true,
        bool $mustChangePassword = false,
        array $overrides = []
    ): User {
        $attributes = array_merge([
            'name' => 'User ' . uniqid(),
            'email' => uniqid('user_', true) . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role?->id,
            'is_active' => $isActive,
            'must_change_password' => $mustChangePassword,
        ], $overrides);

        if (array_key_exists('password', $attributes) && is_string($attributes['password'])) {
            $passwordInfo = password_get_info($attributes['password']);
            $needsHash = empty($passwordInfo['algo']);

            if ($needsHash) {
                $attributes['password'] = bcrypt($attributes['password']);
            }
        }

        return User::query()->create($attributes);
    }

    private function grantTenantAccess(
        User $user,
        Tenant $tenant,
        Role $role,
        bool $isActive = true
    ): TenantUser {
        return TenantUser::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role_id' => $role->id,
            'is_active' => $isActive,
        ]);
    }
}