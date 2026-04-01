<?php
declare(strict_types=1);
namespace Tests\Feature\Observability;
use Illuminate\Support\Str;
use Tests\TestCase;
final class RequestContextHeadersTest extends TestCase
{
    public function test_it_generates_request_id_when_header_is_missing(): void { $this->getJson('/api/v1/health')->assertHeader('X-Request-Id'); }
    public function test_it_generates_trace_id_when_header_is_missing(): void { $this->getJson('/api/v1/health')->assertHeader('X-Trace-Id'); }
    public function test_it_preserves_provided_request_id(): void { $requestId = (string) Str::uuid(); $this->getJson('/api/v1/health', ['X-Request-Id' => $requestId])->assertHeader('X-Request-Id', $requestId); }
    public function test_it_preserves_provided_trace_id(): void { $traceId = (string) Str::uuid(); $this->getJson('/api/v1/health', ['X-Trace-Id' => $traceId])->assertHeader('X-Trace-Id', $traceId); }
}
