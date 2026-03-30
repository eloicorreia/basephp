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
    protected function createRole(
        string $code = 'user',
        string $name = 'User',
        bool $active = true
    ): Role {
        return Role::query()->create([
            'code' => $code,
            'name' => $name,
            'active' => $active,
        ]);
    }

    protected function createTenant(
        string $code = 'tenant-main',
        string $status = 'active',
        ?string $schemaName = null
    ): Tenant {
        return Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => 'Tenant ' . $code,
            'schema_name' => $schemaName ?? 'tenant_' . str_replace('-', '_', $code),
            'status' => $status,
        ]);
    }

    protected function createUser(
        ?Role $role = null,
        bool $isActive = true,
        bool $mustChangePassword = false,
        array $overrides = []
    ): User {
        return User::factory()->create(array_merge([
            'role_id' => $role?->id,
            'is_active' => $isActive,
            'must_change_password' => $mustChangePassword,
        ], $overrides));
    }

    protected function grantTenantAccess(
        User $user,
        Tenant $tenant,
        ?Role $role = null,
        bool $isActive = true
    ): TenantUser {
        $role ??= $this->createRole('tenant-user', 'Tenant User');

        return TenantUser::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role_id' => $role->id,
            'is_active' => $isActive,
        ]);
    }
}