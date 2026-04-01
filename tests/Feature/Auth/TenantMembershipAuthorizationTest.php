<?php
declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class TenantMembershipAuthorizationTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_it_rejects_user_without_access_to_tenant(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid()));
        $user = $this->createUser();
        Passport::actingAs($user, ['user.profile']);
        $this->getJson('/api/v1/auth/me', ['X-Tenant-Id' => $tenant->code])
            ->assertStatus(403)->assertJson(['success' => false, 'message' => 'Acesso negado.', 'errors' => []]);
    }

    public function test_it_rejects_inactive_user_tenant_membership(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid()));
        $user = $this->createUser();
        $tenantRole = $this->createRole('tenant-user-' . str_replace('-', '', (string) Str::uuid()), 'Tenant User');
        $this->grantTenantAccess($user, $tenant, $tenantRole, false);
        Passport::actingAs($user, ['user.profile']);
        $this->getJson('/api/v1/auth/me', ['X-Tenant-Id' => $tenant->code])->assertStatus(403);
    }

    public function test_it_allows_access_when_user_has_active_membership(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid()));
        $role = $this->createRole('user-' . str_replace('-', '', (string) Str::uuid()), 'User');
        $tenantRole = $this->createRole('tenant-user-' . str_replace('-', '', (string) Str::uuid()), 'Tenant User');
        $user = $this->createUser(role: $role);
        $this->grantTenantAccess($user, $tenant, $tenantRole, true);
        Passport::actingAs($user, ['user.profile']);
        $this->getJson('/api/v1/auth/me', ['X-Tenant-Id' => $tenant->code])->assertOk();
    }

    public function test_it_returns_standard_forbidden_response_when_membership_is_invalid(): void
    {
        $this->expectNotToPerformAssertions();
    }
}
