<?php
declare(strict_types=1);
namespace Tests\Feature\Middleware;
use Illuminate\Support\Str;
use Tests\TestCase;
final class RequestContextMiddlewareTest extends TestCase
{
    public function test_it_sets_request_id_in_request_attributes(): void { $this->getJson('/api/v1/health')->assertHeader('X-Request-Id'); }
    public function test_it_sets_trace_id_in_request_attributes(): void { $this->getJson('/api/v1/health')->assertHeader('X-Trace-Id'); }
    public function test_it_preserves_existing_request_id_and_trace_id(): void
    {
        $requestId = (string) Str::uuid(); $traceId = (string) Str::uuid();
        $this->getJson('/api/v1/health', ['X-Request-Id' => $requestId, 'X-Trace-Id' => $traceId])->assertHeader('X-Request-Id', $requestId)->assertHeader('X-Trace-Id', $traceId);
    }
}
