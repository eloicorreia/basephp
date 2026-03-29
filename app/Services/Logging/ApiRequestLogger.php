<?php

declare(strict_types=1);

namespace App\Services\Logging;

use App\Models\ApiRequestLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiRequestLogger
{
    public function log(Request $request, Response $response, int $durationMs, string $status, ?string $message = null): void
    {
        ApiRequestLog::query()->create([
            'request_id' => $request->attributes->get('request_id'),
            'correlation_id' => $request->attributes->get('correlation_id'),
            'tenant_code' => $request->header('X-Tenant-Id'),
            'tenant_id' => app(\App\Support\Tenant\TenantContext::class)->get()?->id,
            'user_id' => $request->user()?->id,
            'oauth_client_id' => $request->user()?->token()?->client_id,
            'method' => $request->method(),
            'route' => $request->route()?->uri(),
            'uri' => $request->getRequestUri(),
            'http_status' => $response->getStatusCode(),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'request_headers' => $this->sanitizeHeaders($request->headers->all()),
            'request_query' => $request->query(),
            'request_body' => $this->sanitizePayload($request->all()),
            'response_body' => $this->sanitizeResponse($response),
            'processing_status' => $status,
            'message' => $message,
            'duration_ms' => $durationMs,
            'created_at' => now(),
        ]);
    }

    private function sanitizeHeaders(array $headers): array
    {
        unset($headers['authorization']);

        return $headers;
    }

    private function sanitizePayload(array $payload): array
    {
        foreach (['password', 'current_password', 'new_password', 'new_password_confirmation', 'client_secret'] as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = '***';
            }
        }

        return $payload;
    }

    private function sanitizeResponse(Response $response): array|string|null
    {
        $content = $response->getContent();

        if ($content === false || $content === '') {
            return null;
        }

        $decoded = json_decode($content, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : mb_substr($content, 0, 4000);
    }
}