<?php

declare(strict_types=1);

namespace Tests\Feature\Web\Admin;

use App\Enums\RoleCode;
use App\Models\ApiRequestLog;
use Illuminate\Support\Str;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminWebLogViewTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_admin_can_view_api_request_logs_with_visual_masking_applied(): void
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
            ->assertSee('***')
            ->assertDontSee('real-token-from-database')
            ->assertDontSee('real-api-key-from-database')
            ->assertDontSee('real-query-secret')
            ->assertDontSee('real-password-secret')
            ->assertDontSee('real-response-token')
            ->assertDontSee('message-secret')
            ->assertDontSee('user-agent-secret');
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
