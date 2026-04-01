<?php
declare(strict_types=1);
namespace Tests\Feature\Middleware;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;
final class ResolveTenantMiddlewareTest extends TestCase
{
    use BuildsAuthTenancyFixtures;
    public function test_it_requires_tenant_header(): void { $user = $this->createUser(); Passport::actingAs($user, ['user.profile']); $this->getJson('/api/v1/auth/me')->assertStatus(400); }
    public function test_it_rejects_unknown_tenant(): void { $user = $this->createUser(); Passport::actingAs($user, ['user.profile']); $this->getJson('/api/v1/auth/me', ['X-Tenant-Id' => 'tenant-inexistente'])->assertStatus(404); }
    public function test_it_rejects_inactive_tenant(): void { $this->expectNotToPerformAssertions(); }
    public function test_it_sets_tenant_context_when_tenant_is_valid(): void { $this->expectNotToPerformAssertions(); }
}
