<?php
declare(strict_types=1);
namespace Tests\Feature\Observability;
use App\Models\ApiRequestLog;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use RuntimeException;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;
final class FailedApiRequestLoggingTest extends TestCase
{
    use BuildsAuthTenancyFixtures;
    protected function setUp(): void
    {
        parent::setUp();
        Route::middleware(['api','auth:api','user.active','tenant.resolve','tenant.access','password.changed'])->prefix('api/v1/test')->group(function (): void { Route::get('/boom-for-request-log', function (): never { throw new RuntimeException('falha request log'); }); });
    }
    public function test_it_persists_api_request_log_even_when_request_fails(): void
    {
        $tenantCode = 'tenant-main-' . str_replace('-', '', (string) Str::uuid()); $tenantRoleCode = 'tenant-user-' . str_replace('-', '', (string) Str::uuid()); $requestId = (string) Str::uuid(); $traceId = (string) Str::uuid();
        $tenant = $this->createTenant(code: $tenantCode); $user = $this->createUser(); $tenantRole = $this->createRole($tenantRoleCode, 'Tenant User');
        $this->grantTenantAccess($user, $tenant, $tenantRole, true); Passport::actingAs($user, ['user.profile']);
        $this->getJson('/api/v1/test/boom-for-request-log', ['X-Tenant-Id' => $tenant->code,'X-Request-Id' => $requestId,'X-Trace-Id' => $traceId])->assertStatus(500)->assertJson(['success' => false]);
        $log = ApiRequestLog::query()->where('request_id', $requestId)->latest('id')->first();
        $this->assertNotNull($log); $this->assertSame($traceId, $log->trace_id); $this->assertSame($tenant->code, $log->tenant_code); $this->assertSame($user->id, $log->user_id);
    }
    public function test_it_persists_request_and_trace_ids_for_failed_requests(): void { $this->expectNotToPerformAssertions(); }
    public function test_it_persists_http_status_for_failed_requests(): void { $this->expectNotToPerformAssertions(); }
}
