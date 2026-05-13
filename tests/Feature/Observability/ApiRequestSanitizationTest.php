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
        $tenant = $this->createTenant(code: 'tenant-main-'.str_replace('-', '', (string) Str::uuid()));
        $requestId = (string) Str::uuid();
        $traceId = (string) Str::uuid();
        $tenantContext = app(TenantContext::class);
        $tenantContext->set($tenant);
        $request = request()->create('/api/v1/fake', 'POST', ['password' => 'secret', 'client_secret' => 'very-secret', 'safe_field' => 'value'], server: ['HTTP_X_TENANT_ID' => $tenant->code]);
        $request->attributes->set('request_id', $requestId);
        $request->attributes->set('trace_id', $traceId);
        $response = response()->json(['success' => true], 200);
        try {
            app(ApiRequestLogger::class)->log(request: $request, response: $response, durationMs: 12, status: 'SUCCESS', message: 'teste');
        } finally {
            $tenantContext->clear();
        }
        $log = ApiRequestLog::query()->where('request_id', $requestId)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('***', $log->request_body['password']);
        $this->assertSame('***', $log->request_body['client_secret']);
        $this->assertSame('value', $log->request_body['safe_field']);
    }

    public function test_api_request_logger_does_not_mask_safe_fields(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main-'.str_replace('-', '', (string) Str::uuid()));
        $requestId = (string) Str::uuid();
        $traceId = (string) Str::uuid();
        $tenantContext = app(TenantContext::class);
        $tenantContext->set($tenant);
        $request = request()->create('/api/v1/fake', 'POST', [
            'name' => 'Safe Name',
            'document' => '12345678900',
        ], server: ['HTTP_X_TENANT_ID' => $tenant->code]);
        $request->attributes->set('request_id', $requestId);
        $request->attributes->set('trace_id', $traceId);
        $response = response()->json(['success' => true], 200);

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

        $log = ApiRequestLog::query()->where('request_id', $requestId)->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('Safe Name', $log->request_body['name']);
        $this->assertSame('12345678900', $log->request_body['document']);
    }

    public function test_api_request_logger_sanitizes_query_headers_and_json_response(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main-'.str_replace('-', '', (string) Str::uuid()));
        $requestId = (string) Str::uuid();
        $traceId = (string) Str::uuid();
        $tenantContext = app(TenantContext::class);
        $tenantContext->set($tenant);
        $request = request()->create('/api/v1/fake?api_key=query-secret&safe_query=value', 'GET', server: [
            'HTTP_AUTHORIZATION' => 'Bearer real-token',
            'HTTP_X_API_KEY' => 'header-secret',
            'HTTP_X_TENANT_ID' => $tenant->code,
        ]);
        $request->attributes->set('request_id', $requestId);
        $request->attributes->set('trace_id', $traceId);
        $response = response()->json([
            'success' => true,
            'data' => [
                'token' => 'response-token',
                'safe_field' => 'safe-response',
            ],
        ], 200);
        try {
            app(ApiRequestLogger::class)->log(request: $request, response: $response, durationMs: 12, status: 'SUCCESS', message: 'teste');
        } finally {
            $tenantContext->clear();
        }
        $log = ApiRequestLog::query()->where('request_id', $requestId)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('***', $log->request_query['api_key']);
        $this->assertSame('value', $log->request_query['safe_query']);
        $this->assertSame('***', $log->request_headers['authorization']);
        $this->assertSame('***', $log->request_headers['x-api-key']);
        $this->assertStringNotContainsString('query-secret', $log->uri);
        $this->assertStringContainsString('api_key=***', $log->uri);
        $this->assertSame('***', $log->response_body['data']['token']);
        $this->assertSame('safe-response', $log->response_body['data']['safe_field']);
    }

    public function test_api_request_logger_masks_nested_sensitive_fields_when_contract_requires_it(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main-'.str_replace('-', '', (string) Str::uuid()));
        $requestId = (string) Str::uuid();
        $traceId = (string) Str::uuid();
        $tenantContext = app(TenantContext::class);
        $tenantContext->set($tenant);
        $request = request()->create('/api/v1/fake', 'POST', [
            'profile' => [
                'name' => 'Safe Name',
                'password' => 'nested-secret',
                'oauth' => [
                    'client_secret' => 'nested-client-secret',
                    'scope' => 'safe-scope',
                ],
            ],
        ], server: ['HTTP_X_TENANT_ID' => $tenant->code]);
        $request->attributes->set('request_id', $requestId);
        $request->attributes->set('trace_id', $traceId);
        $response = response()->json(['success' => true], 200);
        try {
            app(ApiRequestLogger::class)->log(request: $request, response: $response, durationMs: 12, status: 'SUCCESS', message: 'teste');
        } finally {
            $tenantContext->clear();
        }
        $log = ApiRequestLog::query()->where('request_id', $requestId)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('Safe Name', $log->request_body['profile']['name']);
        $this->assertSame('***', $log->request_body['profile']['password']);
        $this->assertSame('***', $log->request_body['profile']['oauth']['client_secret']);
        $this->assertSame('safe-scope', $log->request_body['profile']['oauth']['scope']);
    }
}
