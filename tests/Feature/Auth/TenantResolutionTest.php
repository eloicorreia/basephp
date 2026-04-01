<?php
declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class TenantResolutionTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_it_requires_tenant_header(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user, ['user.profile']);
        $this->getJson('/api/v1/auth/me')->assertStatus(400)->assertJson(['success' => false, 'message' => 'Tenant não informado.', 'errors' => []]);
    }

    public function test_it_rejects_unknown_tenant_code(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user, ['user.profile']);
        $this->getJson('/api/v1/auth/me', ['X-Tenant-Id' => 'tenant-inexistente-' . str_replace('-', '', (string) Str::uuid())])
            ->assertStatus(404)->assertJson(['success' => false, 'message' => 'Tenant não encontrado.', 'errors' => []]);
    }

    public function test_it_rejects_inactive_tenant(): void
    {
        $tenant = $this->createTenant(code: 'tenant-inactive-' . str_replace('-', '', (string) Str::uuid()), status: 'inactive');
        $user = $this->createUser();
        Passport::actingAs($user, ['user.profile']);
        $this->getJson('/api/v1/auth/me', ['X-Tenant-Id' => $tenant->code])
            ->assertStatus(404)->assertJson(['success' => false, 'message' => 'Tenant não encontrado.', 'errors' => []]);
    }

    public function test_it_allows_request_when_tenant_is_active_and_resolved(): void
    {
        $tenant = $this->createTenant(code: 'tenant-active-' . str_replace('-', '', (string) Str::uuid()));
        $user = $this->createUser();
        $tenantRole = $this->createRole('tenant-role-' . str_replace('-', '', (string) Str::uuid()), 'Tenant User');
        $this->grantTenantAccess($user, $tenant, $tenantRole, true);
        Passport::actingAs($user, ['user.profile']);
        $this->getJson('/api/v1/auth/me', ['X-Tenant-Id' => $tenant->code])->assertOk();
    }
}
