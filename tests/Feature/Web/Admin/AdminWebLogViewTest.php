<?php

declare(strict_types=1);

namespace Tests\Feature\Web\Admin;

use App\Enums\RoleCode;
use App\Models\ApiRequestLog;
use App\Services\Admin\Web\AdminWebAuditService;
use Illuminate\Support\Str;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminWebLogViewTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_admin_can_view_api_request_log_detail_without_rendering_raw_payload_and_audits_access(): void
    {
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role);

        $log = ApiRequestLog::query()->create([
            'request_id' => (string) Str::uuid(),
            'trace_id' => (string) Str::uuid(),
            'method' => 'GET',
            'route' => 'api/v1/health',
            'uri' => '/api/v1/health?api_key=real-query-secret',
            'http_status' => 200,
            'ip' => '127.0.0.1',
            'user_agent' => 'Authorization: Bearer user-agent-secret',
            'request_headers' => [
                'authorization' => ['Bearer real-token-from-database'],
                'x-api-key' => ['real-api-key-from-database'],
            ],
            'request_query' => [
                'api_key' => 'real-query-secret',
            ],
            'request_body' => [
                'password' => 'real-password-secret',
            ],
            'response_body' => [
                'token' => 'real-response-token',
            ],
            'processing_status' => 'SUCCESS',
            'duration_ms' => 12,
            'message' => 'Authorization: Bearer message-secret',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user, 'web')
            ->get(route('admin.logs.api-requests.show', $log));

        $response
            ->assertOk()
            ->assertSee('Ver payload detalhado')
            ->assertDontSee('real-token-from-database')
            ->assertDontSee('real-api-key-from-database')
            ->assertDontSee('real-query-secret')
            ->assertDontSee('real-password-secret')
            ->assertDontSee('real-response-token')
            ->assertDontSee('message-secret')
            ->assertDontSee('user-agent-secret');

        $this->assertDatabaseHas('audit_logs', [
            'action' => AdminWebAuditService::LOG_DETAIL_VIEW,
            'auditable_type' => ApiRequestLog::class,
            'auditable_id' => $log->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_admin_can_view_masked_payload_with_extra_permission_and_audit_access(): void
    {
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role);

        config()->set('admin_web.logs.detail_text_limit', 120);

        $log = ApiRequestLog::query()->create([
            'request_id' => (string) Str::uuid(),
            'trace_id' => (string) Str::uuid(),
            'method' => 'POST',
            'route' => 'api/v1/admin/users',
            'uri' => '/api/v1/admin/users',
            'http_status' => 201,
            'ip' => '127.0.0.1',
            'request_headers' => [
                'authorization' => ['Bearer raw-authorization-token'],
                'x-api-key' => ['raw-api-key'],
            ],
            'request_query' => [
                'api_key' => 'raw-query-key',
            ],
            'request_body' => [
                'password' => 'raw-password',
                'description' => str_repeat('A', 200),
            ],
            'response_body' => [
                'token' => 'raw-response-token',
            ],
            'processing_status' => 'SUCCESS',
            'duration_ms' => 21,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user, 'web')
            ->get(route('admin.logs.api-requests.payload', $log));

        $response
            ->assertOk()
            ->assertSee('***')
            ->assertSee('...[truncated]')
            ->assertDontSee('raw-authorization-token')
            ->assertDontSee('raw-api-key')
            ->assertDontSee('raw-query-key')
            ->assertDontSee('raw-password')
            ->assertDontSee('raw-response-token');

        $this->assertDatabaseHas('audit_logs', [
            'action' => AdminWebAuditService::LOG_PAYLOAD_VIEW,
            'auditable_type' => ApiRequestLog::class,
            'auditable_id' => $log->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_api_request_log_index_applies_mandatory_period_filter_and_pagination(): void
    {
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role);

        ApiRequestLog::query()->create([
            'method' => 'GET',
            'route' => 'api/v1/old-log',
            'uri' => '/api/v1/old-log',
            'http_status' => 200,
            'processing_status' => 'SUCCESS',
            'created_at' => now()->subDays(40),
        ]);

        ApiRequestLog::query()->create([
            'method' => 'GET',
            'route' => 'api/v1/recent-log',
            'uri' => '/api/v1/recent-log',
            'http_status' => 200,
            'processing_status' => 'SUCCESS',
            'created_at' => now(),
        ]);

        $this->actingAs($user, 'web')
            ->get(route('admin.logs.api-requests.index', ['per_page' => 500]))
            ->assertOk()
            ->assertSee('name="date_from"', false)
            ->assertSee('name="date_to"', false)
            ->assertSee(now()->toDateString())
            ->assertSee('api/v1/recent-log')
            ->assertDontSee('api/v1/old-log')
            ->assertSee('value="50"', false);
    }

    public function test_guest_cannot_view_admin_logs(): void
    {
        $log = ApiRequestLog::query()->create([
            'method' => 'GET',
            'uri' => '/api/v1/health',
            'http_status' => 200,
            'processing_status' => 'SUCCESS',
            'created_at' => now(),
        ]);

        $this->get(route('admin.logs.api-requests.show', $log))
            ->assertRedirect('/admin/login');
    }
}
