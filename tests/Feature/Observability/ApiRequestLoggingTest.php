<?php

declare(strict_types=1);

namespace Tests\Feature\Observability;

use App\Models\ApiRequestLog;
use App\Services\Logging\ApiRequestLogger;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class ApiRequestLoggingTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    private const HEALTH_REQUEST_ID = '11111111-1111-1111-1111-111111111111';
    private const HEALTH_TRACE_ID = '22222222-2222-2222-2222-222222222222';

    private const AUTH_ME_REQUEST_ID = '33333333-3333-3333-3333-333333333333';
    private const AUTH_ME_TRACE_ID = '44444444-4444-4444-4444-444444444444';

    private const SANITIZE_REQUEST_ID = '55555555-5555-5555-5555-555555555555';
    private const SANITIZE_TRACE_ID = '66666666-6666-6666-6666-666666666666';

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
            'X-Request-Id' => self::HEALTH_REQUEST_ID,
            'X-Trace-Id' => self::HEALTH_TRACE_ID,
        ]);

        $response->assertOk();
        $response->assertHeader('X-Request-Id', self::HEALTH_REQUEST_ID);
        $response->assertHeader('X-Trace-Id', self::HEALTH_TRACE_ID);
    }

    public function test_successful_tenant_request_is_persisted_in_api_request_logs(): void
    {
        $tenantCode = 'tenant-main-' . str_replace('-', '', (string) Str::uuid());
        $userRoleCode = 'user-' . str_replace('-', '', (string) Str::uuid());
        $tenantRoleCode = 'tenant-user-' . str_replace('-', '', (string) Str::uuid());

        $tenant = $this->createTenant(code: $tenantCode);
        $userRole = $this->createRole($userRoleCode, 'User');
        $tenantRole = $this->createRole($tenantRoleCode, 'Tenant User');
        $user = $this->createUser(role: $userRole);

        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => $tenant->code,
            'X-Request-Id' => self::AUTH_ME_REQUEST_ID,
            'X-Trace-Id' => self::AUTH_ME_TRACE_ID,
        ]);

        $response->assertOk();

        $log = ApiRequestLog::query()
            ->where('request_id', self::AUTH_ME_REQUEST_ID)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(self::AUTH_ME_REQUEST_ID, $log->request_id);
        $this->assertSame(self::AUTH_ME_TRACE_ID, $log->trace_id);
        $this->assertNull($log->tenant_id);
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
        $tenantCode = 'tenant-main-' . str_replace('-', '', (string) Str::uuid());
        $tenant = $this->createTenant(code: $tenantCode);

        /** @var TenantContext $tenantContext */
        $tenantContext = app(TenantContext::class);
        $tenantContext->set($tenant);

        $request = request()->create('/api/v1/fake', 'POST', [
            'password' => 'secret',
            'client_secret' => 'very-secret',
            'safe_field' => 'value',
        ], server: [
            'HTTP_X_TENANT_ID' => $tenant->code,
        ]);

        $request->attributes->set('request_id', self::SANITIZE_REQUEST_ID);
        $request->attributes->set('trace_id', self::SANITIZE_TRACE_ID);

        $response = response()->json([
            'success' => true,
        ], 200);

        try {
            app(ApiRequestLogger::class)->log(
                request: $request,
                response: $response,
                durationMs: 12,
                status: 'SUCCESS',
                message: 'teste'
            );
        } finally {
            $tenantContext->clear();
        }

        $log = ApiRequestLog::query()
            ->where('request_id', self::SANITIZE_REQUEST_ID)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(self::SANITIZE_REQUEST_ID, $log->request_id);
        $this->assertSame(self::SANITIZE_TRACE_ID, $log->trace_id);
        $this->assertNull($log->tenant_id);
        $this->assertSame($tenant->code, $log->tenant_code);
        $this->assertSame('***', $log->request_body['password']);
        $this->assertSame('***', $log->request_body['client_secret']);
        $this->assertSame('value', $log->request_body['safe_field']);
    }
}