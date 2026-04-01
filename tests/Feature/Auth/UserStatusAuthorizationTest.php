<?php
declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class UserStatusAuthorizationTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_it_blocks_user_marked_as_inactive(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid()));
        $user = $this->createUser(isActive: false);
        $tenantRole = $this->createRole('tenant-user-' . str_replace('-', '', (string) Str::uuid()), 'Tenant User');
        $this->grantTenantAccess($user, $tenant, $tenantRole, true);
        Passport::actingAs($user, ['user.profile']);
        $this->getJson('/api/v1/auth/me', ['X-Tenant-Id' => $tenant->code])->assertStatus(403);
    }

    public function test_it_blocks_user_that_must_change_password(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid()));
        $user = $this->createUser(mustChangePassword: true);
        $tenantRole = $this->createRole('tenant-user-' . str_replace('-', '', (string) Str::uuid()), 'Tenant User');
        $this->grantTenantAccess($user, $tenant, $tenantRole, true);
        Passport::actingAs($user, ['user.profile']);
        $this->getJson('/api/v1/auth/me', ['X-Tenant-Id' => $tenant->code])->assertStatus(403);
    }

    public function test_it_allows_active_user_with_password_already_changed(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid()));
        $user = $this->createUser();
        $tenantRole = $this->createRole('tenant-user-' . str_replace('-', '', (string) Str::uuid()), 'Tenant User');
        $this->grantTenantAccess($user, $tenant, $tenantRole, true);
        Passport::actingAs($user, ['user.profile']);
        $this->getJson('/api/v1/auth/me', ['X-Tenant-Id' => $tenant->code])->assertOk();
    }
}
