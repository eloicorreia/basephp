<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Models\ApiRequestLog;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

final class ApiRequestLoggingMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['api'])
            ->prefix('api/v1/test/middleware/logging')
            ->group(function (): void {
                Route::get('/success', fn () => response()->json(['success' => true]));
                Route::get('/failure', function (): never {
                    throw new RuntimeException('falha controlada de logging');
                });
            });
    }

    public function test_it_persists_api_request_log_after_successful_response(): void
    {
        $requestId = (string) Str::uuid();
        $traceId = (string) Str::uuid();

        $this->getJson('/api/v1/test/middleware/logging/success', [
            'X-Request-Id' => $requestId,
            'X-Trace-Id' => $traceId,
        ])->assertOk();

        $log = ApiRequestLog::query()
            ->where('request_id', $requestId)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($traceId, $log->trace_id);
        $this->assertSame(200, $log->http_status);
        $this->assertSame('SUCCESS', $log->processing_status);
    }

    public function test_it_persists_api_request_log_after_failed_response(): void
    {
        $requestId = (string) Str::uuid();
        $traceId = (string) Str::uuid();

        $this->getJson('/api/v1/test/middleware/logging/failure', [
            'X-Request-Id' => $requestId,
            'X-Trace-Id' => $traceId,
        ])->assertStatus(500);

        $log = ApiRequestLog::query()
            ->where('request_id', $requestId)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($traceId, $log->trace_id);
        $this->assertSame(500, $log->http_status);
        $this->assertSame('ERROR', $log->processing_status);
    }
}
