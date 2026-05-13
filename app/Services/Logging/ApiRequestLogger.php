<?php

declare(strict_types=1);

namespace App\Services\Logging;

use App\Models\ApiRequestLog;
use App\Support\Logging\SensitiveDataSanitizer;
use App\Support\Tenant\TenantContext;
use Illuminate\Http\Request;
use Laravel\Passport\Token;
use Symfony\Component\HttpFoundation\Response;

class ApiRequestLogger
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SensitiveDataSanitizer $sensitiveDataSanitizer,
    ) {}

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
            'uri' => $this->sanitizeText($request->getRequestUri(), 1000),
            'http_status' => $response->getStatusCode(),
            'ip' => $request->ip(),
            'user_agent' => $this->sanitizeText((string) $request->userAgent(), 1000),
            'request_headers' => $this->sanitizeHeaders($request->headers->all()),
            'request_query' => $this->sanitizePayload($request->query()),
            'request_body' => $this->sanitizePayload($request->all()),
            'response_body' => $this->sanitizeResponse($response),
            'processing_status' => $status,
            'message' => $message !== null ? $this->sanitizeText($message) : null,
            'duration_ms' => $durationMs,
            'created_at' => now(),
        ]);
    }

    private function resolveOauthClientId(Request $request): ?string
    {
        $attributeClientId = $request->attributes->get('oauth_client_id');

        if ($attributeClientId !== null) {
            return (string) $attributeClientId;
        }

        $token = $request->user()?->token();

        if (! $token instanceof Token || $token->client_id === null) {
            return null;
        }

        return (string) $token->client_id;
    }

    /**
     * @param  array<string, list<string|null>>  $headers
     * @return array<mixed>
     */
    private function sanitizeHeaders(array $headers): array
    {
        return $this->sanitizePayload($headers);
    }

    /**
     * @param  array<mixed>  $payload
     * @return array<mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        return $this->sensitiveDataSanitizer->sanitizeArray($payload) ?? [];
    }

    /**
     * @return array<mixed>|string|null
     */
    private function sanitizeResponse(Response $response): array|string|null
    {
        $content = $response->getContent();

        if ($content === false || $content === '') {
            return null;
        }

        $decoded = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return is_array($decoded)
                ? $this->sanitizePayload($decoded)
                : $this->sanitizeScalarResponse($decoded);
        }

        return $this->sanitizeText($content);
    }

    private function sanitizeScalarResponse(mixed $decoded): mixed
    {
        if (is_string($decoded)) {
            return $this->sanitizeText($decoded);
        }

        return $decoded;
    }

    private function sanitizeText(string $value, int $maxLength = 4000): string
    {
        return $this->sensitiveDataSanitizer->sanitizeText($value, maxLength: $maxLength);
    }
}
