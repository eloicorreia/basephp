<?php

declare(strict_types=1);

namespace Tests\Feature\Observability;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use RuntimeException;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class ExceptionHandlingAndSystemLogTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware([
            'api',
            'auth:api',
        ])->prefix('api/v1/test')->group(function (): void {
            Route::get('/boom', function (): never {
                throw new RuntimeException('falha interna');
            });

            Route::post('/validation-error', function (\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse {
                $validated = validator(
                    $request->all(),
                    [
                        'name' => ['required', 'string', 'min:3'],
                    ]
                )->validate();

                return response()->json([
                    'success' => true,
                    'data' => $validated,
                ]);
            });
        });
    }

    public function test_it_returns_standard_json_and_persists_system_log_for_internal_exception(): void
    {
        $tenantCode = 'tenant-main-' . str_replace('-', '', (string) Str::uuid());
        $userRoleCode = 'user-' . str_replace('-', '', (string) Str::uuid());
        $tenantRoleCode = 'tenant-user-' . str_replace('-', '', (string) Str::uuid());
        $requestId = (string) Str::uuid();
        $traceId = (string) Str::uuid();

        $tenant = $this->createTenant(code: $tenantCode);
        $userRole = $this->createRole($userRoleCode, 'User');
        $tenantRole = $this->createRole($tenantRoleCode, 'Tenant User');
        $user = $this->createUser(role: $userRole);

        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/test/boom', [
            'X-Tenant-Id' => $tenant->code,
            'X-Request-Id' => $requestId,
            'X-Trace-Id' => $traceId,
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Erro ao processar a requisição.',
                'errors' => [],
            ]);

        $this->assertTrue(
            SystemLog::query()->where([
                'request_id' => $requestId,
                'trace_id' => $traceId,
                'user_id' => $user->id,
                'route' => 'api/v1/test/boom',
                'method' => 'GET',
            ])->exists()
        );
    }

    public function test_it_returns_standard_json_for_validation_error_and_persists_system_log(): void
    {
        $tenantCode = 'tenant-main-' . str_replace('-', '', (string) Str::uuid());
        $userRoleCode = 'user-' . str_replace('-', '', (string) Str::uuid());
        $tenantRoleCode = 'tenant-user-' . str_replace('-', '', (string) Str::uuid());
        $requestId = (string) Str::uuid();
        $traceId = (string) Str::uuid();

        $tenant = $this->createTenant(code: $tenantCode);
        $userRole = $this->createRole($userRoleCode, 'User');
        $tenantRole = $this->createRole($tenantRoleCode, 'Tenant User');
        $user = $this->createUser(role: $userRole);

        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        $response = $this->postJson('/api/v1/test/validation-error', [
            'name' => 'a',
        ], [
            'X-Tenant-Id' => $tenant->code,
            'X-Request-Id' => $requestId,
            'X-Trace-Id' => $traceId,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);

        $this->assertTrue(
            SystemLog::query()->where([
                'request_id' => $requestId,
                'trace_id' => $traceId,
                'user_id' => $user->id,
                'route' => 'api/v1/test/validation-error',
                'method' => 'POST',
            ])->exists()
        );
    }
}