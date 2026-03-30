<?php

declare(strict_types=1);

namespace Tests\Feature\Observability;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;
use RuntimeException;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class ExceptionHandlingAndSystemLogTest extends TestCase
{
    use BuildsAuthTenancyFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware([
            'api',
            'auth:api',
            'user.active',
            'tenant.resolve',
            'tenant.access',
            'password.changed',
        ])->prefix('api/v1/test')->group(function (): void {
            Route::get('/boom', function (): never {
                throw new RuntimeException('falha controlada para teste');
            });

            Route::post('/validation-error', function (): array {
                request()->validate([
                    'name' => ['required', 'string', 'min:3'],
                ]);

                return ['ok' => true];
            });
        });
    }

    public function test_it_returns_standard_json_and_persists_system_log_for_internal_exception(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main');
        $user = $this->createUser();
        $tenantRole = $this->createRole('tenant-user', 'Tenant User');

        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/test/boom', [
            'X-Tenant-Id' => $tenant->code,
            'X-Request-Id' => 'req-exception',
            'X-Trace-Id' => 'trace-exception',
        ]);

        $response->assertStatus(500)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ])
            ->assertJson([
                'success' => false,
            ]);

        $this->assertDatabaseHas('system_logs', [
            'request_id' => 'req-exception',
            'trace_id' => 'trace-exception',
            'user_id' => $user->id,
            'route' => 'api/v1/test/boom',
            'method' => 'GET',
        ]);
    }

    public function test_it_returns_standard_json_for_validation_error_and_persists_system_log(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main');
        $user = $this->createUser();
        $tenantRole = $this->createRole('tenant-user', 'Tenant User');

        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        $response = $this->postJson('/api/v1/test/validation-error', [
            'name' => 'a',
        ], [
            'X-Tenant-Id' => $tenant->code,
            'X-Request-Id' => 'req-validation',
            'X-Trace-Id' => 'trace-validation',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ])
            ->assertJson([
                'success' => false,
            ]);

        $this->assertDatabaseHas('system_logs', [
            'request_id' => 'req-validation',
            'trace_id' => 'trace-validation',
            'user_id' => $user->id,
            'route' => 'api/v1/test/validation-error',
            'method' => 'POST',
        ]);
    }
}