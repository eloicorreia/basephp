<?php

declare(strict_types=1);

namespace Tests\Feature\Observability;

use App\Models\ApiRequestLog;
use Tests\TestCase;

final class LogPruningCommandTest extends TestCase
{
    public function test_it_prunes_database_logs_using_configured_retention(): void
    {
        config([
            'observability.retention.enabled' => true,
            'observability.retention.public_tables' => [
                'api_request_logs' => [
                    'column' => 'created_at',
                    'days' => 30,
                ],
            ],
        ]);

        $oldLog = ApiRequestLog::query()->create([
            'method' => 'GET',
            'uri' => '/api/v1/old',
            'http_status' => 200,
            'processing_status' => 'SUCCESS',
            'created_at' => now()->subDays(45),
        ]);
        $recentLog = ApiRequestLog::query()->create([
            'method' => 'GET',
            'uri' => '/api/v1/recent',
            'http_status' => 200,
            'processing_status' => 'SUCCESS',
            'created_at' => now()->subDays(5),
        ]);

        $this->artisan('logs:prune')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('api_request_logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('api_request_logs', ['id' => $recentLog->id]);
    }

    public function test_it_supports_dry_run_without_deleting_logs(): void
    {
        config([
            'observability.retention.enabled' => true,
            'observability.retention.public_tables' => [
                'api_request_logs' => [
                    'column' => 'created_at',
                    'days' => 30,
                ],
            ],
        ]);

        $oldLog = ApiRequestLog::query()->create([
            'method' => 'GET',
            'uri' => '/api/v1/old',
            'http_status' => 200,
            'processing_status' => 'SUCCESS',
            'created_at' => now()->subDays(45),
        ]);

        $this->artisan('logs:prune', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertDatabaseHas('api_request_logs', ['id' => $oldLog->id]);
    }
}
