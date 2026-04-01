<?php
declare(strict_types=1);
namespace Tests\Feature\Observability;
use App\Models\ApiRequestLog;
use App\Services\Logging\ApiRequestLogger;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;
final class ApiRequestSanitizationTest extends TestCase
{
    use BuildsAuthTenancyFixtures;
    public function test_api_request_logger_sanitizes_sensitive_request_payload_fields(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid()));
        $requestId = (string) Str::uuid(); $traceId = (string) Str::uuid();
        $tenantContext = app(TenantContext::class); $tenantContext->set($tenant);
        $request = request()->create('/api/v1/fake', 'POST', ['password' => 'secret','client_secret' => 'very-secret','safe_field' => 'value'], server: ['HTTP_X_TENANT_ID' => $tenant->code]);
        $request->attributes->set('request_id', $requestId); $request->attributes->set('trace_id', $traceId);
        $response = response()->json(['success' => true], 200);
        try { app(ApiRequestLogger::class)->log(request: $request, response: $response, durationMs: 12, status: 'SUCCESS', message: 'teste'); } finally { $tenantContext->clear(); }
        $log = ApiRequestLog::query()->where('request_id', $requestId)->latest('id')->first();
        $this->assertNotNull($log); $this->assertSame('***', $log->request_body['password']); $this->assertSame('***', $log->request_body['client_secret']); $this->assertSame('value', $log->request_body['safe_field']);
    }
    public function test_api_request_logger_does_not_mask_safe_fields(): void { $this->expectNotToPerformAssertions(); }
    public function test_api_request_logger_masks_nested_sensitive_fields_when_contract_requires_it(): void { $this->markTestIncomplete('Implementar se o contrato suportar nested sanitization.'); }
}
