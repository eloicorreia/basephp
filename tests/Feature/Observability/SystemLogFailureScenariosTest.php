<?php

declare(strict_types=1);

namespace Tests\Feature\Observability;

use App\Models\SystemLog;
use App\Services\Logging\LogPersistenceService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use RuntimeException;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class SystemLogFailureScenariosTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['api'])
            ->prefix('api/v1/test/system-log')
            ->group(function (): void {
                Route::get('/before-tenant', function (): never {
                    throw new RuntimeException('falha antes do tenant');
                });

                Route::post('/validation', function (Request $request): JsonResponse {
                    $validated = validator($request->all(), [
                        'name' => ['required', 'string', 'min:3'],
                    ])->validate();

                    return response()->json(['success' => true, 'data' => $validated]);
                });
            });

        Route::middleware(['api', 'auth:api'])
            ->prefix('api/v1/test/system-log')
            ->get('/authorization', function (): never {
                throw new AuthorizationException('sem permissao controlada');
            });
    }

    public function test_it_persists_system_log_when_exception_occurs_before_tenant_resolution(): void
    {
        $requestId = (string) Str::uuid();
        $traceId = (string) Str::uuid();

        $this->getJson('/api/v1/test/system-log/before-tenant', [
            'X-Request-Id' => $requestId,
            'X-Trace-Id' => $traceId,
        ])->assertStatus(500);

        $this->assertDatabaseHas('system_logs', [
            'request_id' => $requestId,
            'trace_id' => $traceId,
            'category' => 'system',
            'operation' => 'exception_handler',
            'http_status' => 500,
            'processing_status' => 'error',
        ]);
    }

    public function test_it_persists_system_log_when_authorization_fails(): void
    {
        $requestId = (string) Str::uuid();
        $traceId = (string) Str::uuid();
        $user = $this->createUser();

        Passport::actingAs($user, ['user.profile']);

        $this->getJson('/api/v1/test/system-log/authorization', [
            'X-Request-Id' => $requestId,
            'X-Trace-Id' => $traceId,
        ])->assertStatus(403);

        $this->assertDatabaseHas('system_logs', [
            'request_id' => $requestId,
            'trace_id' => $traceId,
            'category' => 'http',
            'operation' => 'exception_handler',
            'user_id' => $user->id,
            'http_status' => 403,
            'processing_status' => 'error',
        ]);
    }

    public function test_it_persists_system_log_when_validation_fails(): void
    {
        $requestId = (string) Str::uuid();
        $traceId = (string) Str::uuid();

        $this->postJson('/api/v1/test/system-log/validation', [
            'name' => 'ab',
        ], [
            'X-Request-Id' => $requestId,
            'X-Trace-Id' => $traceId,
        ])->assertStatus(422);

        $this->assertDatabaseHas('system_logs', [
            'request_id' => $requestId,
            'trace_id' => $traceId,
            'category' => 'validation',
            'operation' => 'exception_handler',
            'http_status' => 422,
            'processing_status' => 'error',
        ]);
    }

    public function test_it_does_not_persist_sensitive_data_in_system_log_context(): void
    {
        $requestId = (string) Str::uuid();
        $traceId = (string) Str::uuid();
        request()->attributes->set('request_id', $requestId);
        request()->attributes->set('trace_id', $traceId);

        app(LogPersistenceService::class)->logSystemInfo(
            message: 'Teste de sanitização.',
            category: 'observability',
            operation: 'sanitize_system_log',
            context: [
                'safe' => 'visible',
                'credentials' => [
                    'password' => 'secret',
                    'client_secret' => 'client-secret',
                    'nested' => [
                        'refresh_token' => 'refresh-secret',
                        'safe_nested' => 'still-visible',
                    ],
                ],
            ],
        );

        $log = SystemLog::query()
            ->where('request_id', $requestId)
            ->where('trace_id', $traceId)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('visible', $log->context['safe']);
        $this->assertSame('***', $log->context['credentials']);
    }

    public function test_it_does_not_persist_sensitive_data_in_system_log_message(): void
    {
        $requestId = (string) Str::uuid();
        request()->attributes->set('request_id', $requestId);

        app(LogPersistenceService::class)->logSystemError(
            throwable: new RuntimeException('Falha externa password=secret Authorization: Bearer real-token'),
            category: 'observability',
            operation: 'sanitize_system_log_message',
        );

        $log = SystemLog::query()
            ->where('request_id', $requestId)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringNotContainsString('password=secret', $log->message);
        $this->assertStringNotContainsString('real-token', $log->message);
        $this->assertStringContainsString('password=***', $log->message);
        $this->assertStringContainsString('Authorization: ***', $log->message);
    }
}
