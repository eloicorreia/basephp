<?php

declare(strict_types=1);

namespace App\Services\Logging;

use App\Models\ApiRequestLog;
use App\Support\Tenant\TenantContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiRequestLogger
{
    public function __construct(
        private readonly TenantContext $tenantContext
    ) {
    }

    public function log(
        Request $request,
        Response $response,
        int $durationMs,
        string $status,
        ?string $message = null
    ): void {
        ApiRequestLog::query()->create([
            'request_id' => $request->attributes->get('request_id'),
            'trace_id' => $request->attributes->get('trace_id'),
            'tenant_id' => $this->tenantContext->get()?->id,
            'tenant_code' => $request->header('X-Tenant-Id'),
            'user_id' => $request->user()?->id,
            'oauth_client_id' => $this->resolveOauthClientId($request),
            'method' => $request->method(),
            'route' => $request->route()?->uri(),
            'uri' => $request->getRequestUri(),
            'http_status' => $response->getStatusCode(),
            'ip' => $request->ip(),
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

    private function resolveOauthClientId(Request $request): ?string
    {
        $token = $request->user()?->token();

        if ($token === null || $token->client_id === null) {
            return null;
        }

        return (string) $token->client_id;
    }

    private function sanitizeHeaders(array $headers): array
    {
        unset($headers['authorization']);

        return $headers;
    }

    private function sanitizePayload(array $payload): array
    {
        foreach ([
            'password',
            'current_password',
            'new_password',
            'new_password_confirmation',
            'client_secret',
        ] as $field) {
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

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return mb_substr($content, 0, 4000);
    }
}