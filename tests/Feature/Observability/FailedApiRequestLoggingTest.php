<?php

declare(strict_types=1);

namespace Tests\Feature\Observability;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;
use RuntimeException;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class FailedApiRequestLoggingTest extends TestCase
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
            Route::get('/boom-for-request-log', function (): never {
                throw new RuntimeException('falha request log');
            });
        });
    }

    public function test_it_persists_api_request_log_even_when_request_fails(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main');
        $user = $this->createUser();
        $tenantRole = $this->createRole('tenant-user', 'Tenant User');

        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/test/boom-for-request-log', [
            'X-Tenant-Id' => $tenant->code,
            'X-Request-Id' => 'req-failed-log',
            'X-Trace-Id' => 'trace-failed-log',
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
            ]);

        $log = \App\Models\ApiRequestLog::query()
            ->where('request_id', 'req-failed-log')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('trace-failed-log', $log->trace_id);
        $this->assertSame($tenant->id, $log->tenant_id);
        $this->assertSame($tenant->code, $log->tenant_code);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('GET', $log->method);
        $this->assertSame('api/v1/test/boom-for-request-log', $log->route);
        $this->assertSame('/api/v1/test/boom-for-request-log', $log->uri);
        $this->assertSame(500, $log->http_status);

        $this->assertContains(
            $log->processing_status,
            ['ERROR', 'FAILED', 'FAILURE']
        );
    }
}