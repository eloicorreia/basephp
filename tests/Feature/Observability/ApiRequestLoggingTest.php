<?php

declare(strict_types=1);

namespace Tests\Feature\Observability;

use App\Models\ApiRequestLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

class ApiRequestLoggingTest extends TestCase
{
    use BuildsAuthTenancyFixtures;
    use RefreshDatabase;

    public function test_health_endpoint_returns_request_and_trace_headers(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk();
        $response->assertHeader('X-Request-Id');
        $response->assertHeader('X-Trace-Id');
    }

    public function test_health_endpoint_preserves_incoming_request_and_trace_headers(): void
    {
        $response = $this->getJson('/api/v1/health', [
            'X-Request-Id' => 'req-fixed-id',
            'X-Trace-Id' => 'trace-fixed-id',
        ]);

        $response->assertOk();
        $response->assertHeader('X-Request-Id', 'req-fixed-id');
        $response->assertHeader('X-Trace-Id', 'trace-fixed-id');
    }

    public function test_successful_tenant_request_is_persisted_in_api_request_logs(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main');
        $userRole = $this->createRole('user', 'User');
        $tenantRole = $this->createRole('tenant-user', 'Tenant User');
        $user = $this->createUser(role: $userRole);

        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => $tenant->code,
            'X-Request-Id' => 'req-auth-me',
            'X-Trace-Id' => 'trace-auth-me',
        ]);

        $response->assertOk();

        $log = ApiRequestLog::query()->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('req-auth-me', $log->request_id);
        $this->assertSame('trace-auth-me', $log->trace_id);
        $this->assertSame($tenant->id, $log->tenant_id);
        $this->assertSame($tenant->code, $log->tenant_code);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('GET', $log->method);
        $this->assertSame('api/v1/auth/me', $log->route);
        $this->assertSame('/api/v1/auth/me', $log->uri);
        $this->assertSame(200, $log->http_status);
        $this->assertSame('SUCCESS', $log->processing_status);
        $this->assertIsInt($log->duration_ms);
    }

    public function test_api_request_logger_sanitizes_sensitive_request_payload_fields(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main');
        $tenantContext = app(\App\Support\Tenant\TenantContext::class);
        $tenantContext->set($tenant);

        $request = request()->create('/api/v1/fake', 'POST', [
            'password' => 'secret',
            'client_secret' => 'very-secret',
            'safe_field' => 'value',
        ], server: [
            'HTTP_X_TENANT_ID' => $tenant->code,
        ]);

        $request->attributes->set('request_id', 'req-sanitize');
        $request->attributes->set('trace_id', 'trace-sanitize');

        $response = response()->json([
            'success' => true,
        ], 200);

        app(\App\Services\Logging\ApiRequestLogger::class)->log(
            request: $request,
            response: $response,
            durationMs: 12,
            status: 'SUCCESS',
            message: 'teste'
        );

        $tenantContext->clear();

        $log = ApiRequestLog::query()->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('***', $log->request_body['password']);
        $this->assertSame('***', $log->request_body['client_secret']);
        $this->assertSame('value', $log->request_body['safe_field']);
    }
}