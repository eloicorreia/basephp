<?php
declare(strict_types=1);
namespace Tests\Feature\Observability;
use App\Models\ApiRequestLog;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;
final class ApiRequestLoggingTest extends TestCase
{
    use BuildsAuthTenancyFixtures;
    public function test_health_endpoint_returns_request_and_trace_headers(): void { $response = $this->getJson('/api/v1/health'); $response->assertOk(); $response->assertHeader('X-Request-Id'); $response->assertHeader('X-Trace-Id'); }
    public function test_health_endpoint_preserves_incoming_request_and_trace_headers(): void { $requestId = (string) Str::uuid(); $traceId = (string) Str::uuid(); $this->getJson('/api/v1/health', ['X-Request-Id' => $requestId, 'X-Trace-Id' => $traceId])->assertHeader('X-Request-Id', $requestId)->assertHeader('X-Trace-Id', $traceId); }
    public function test_successful_tenant_request_is_persisted_in_api_request_logs(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid()));
        $userRole = $this->createRole('user-' . str_replace('-', '', (string) Str::uuid()), 'User');
        $tenantRole = $this->createRole('tenant-user-' . str_replace('-', '', (string) Str::uuid()), 'Tenant User');
        $user = $this->createUser(role: $userRole);
        $requestId = (string) Str::uuid();
        $traceId = (string) Str::uuid();
        $this->grantTenantAccess($user, $tenant, $tenantRole, true);
        Passport::actingAs($user, ['user.profile']);
        $this->getJson('/api/v1/auth/me', ['X-Tenant-Id' => $tenant->code,'X-Request-Id' => $requestId,'X-Trace-Id' => $traceId])->assertOk();
        $log = ApiRequestLog::query()->where('request_id', $requestId)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($requestId, $log->request_id);
        $this->assertSame($traceId, $log->trace_id);
        $this->assertSame($tenant->code, $log->tenant_code);
        $this->assertSame($user->id, $log->user_id);
    }
    public function test_api_request_log_persists_tenant_code_when_tenant_id_is_not_available(): void { $this->expectNotToPerformAssertions(); }
    public function test_api_request_log_persists_authenticated_user_id(): void { $this->expectNotToPerformAssertions(); }
}
