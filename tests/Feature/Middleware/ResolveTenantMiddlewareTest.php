<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Support\Tenant\TenantContext;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class ResolveTenantMiddlewareTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['api', 'auth:api', 'tenant.resolve'])
            ->prefix('api/v1/test/middleware')
            ->get('/tenant-context', function () {
                $tenant = app(TenantContext::class)->require();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'tenant_id' => $tenant->id,
                        'tenant_code' => $tenant->code,
                    ],
                ]);
            });
    }

    public function test_it_requires_tenant_header(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user, ['user.profile']);

        $this->getJson('/api/v1/auth/me')->assertStatus(400);
    }

    public function test_it_rejects_unknown_tenant(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user, ['user.profile']);

        $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => 'tenant-inexistente',
        ])->assertStatus(404);
    }

    public function test_it_rejects_inactive_tenant(): void
    {
        $tenant = $this->createTenant(
            code: 'tenant-inactive-'.str_replace('-', '', (string) Str::uuid()),
            isActive: false
        );
        $user = $this->createUser();
        Passport::actingAs($user, ['user.profile']);

        $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => $tenant->code,
        ])->assertStatus(404);
    }

    public function test_it_sets_tenant_context_when_tenant_is_valid(): void
    {
        $tenant = $this->createTenant(code: 'tenant-context-'.str_replace('-', '', (string) Str::uuid()));
        $user = $this->createUser();
        Passport::actingAs($user, ['user.profile']);

        $this->getJson('/api/v1/test/middleware/tenant-context', [
            'X-Tenant-Id' => $tenant->code,
        ])->assertOk()
            ->assertJsonPath('data.tenant_id', $tenant->id)
            ->assertJsonPath('data.tenant_code', $tenant->code);

        $this->assertFalse(app(TenantContext::class)->hasTenant());
    }
}
