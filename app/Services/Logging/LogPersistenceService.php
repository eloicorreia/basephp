<?php

declare(strict_types=1);

namespace App\Services\Logging;

use App\Models\AuditLog;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Throwable;

class LogPersistenceService
{
    public function logSystemInfo(
        string $message,
        string $category,
        string $operation,
        ?int $userId = null,
        ?array $context = null,
        ?int $httpStatus = null,
        ?string $processingStatus = null,
    ): void {
        $this->persistSystemLog(
            level: 'info',
            message: $message,
            category: $category,
            operation: $operation,
            userId: $userId,
            context: $context,
            httpStatus: $httpStatus,
            processingStatus: $processingStatus,
        );
    }

    public function logSystemWarning(
        string $message,
        string $category,
        string $operation,
        ?int $userId = null,
        ?array $context = null,
        ?int $httpStatus = null,
        ?string $processingStatus = null,
    ): void {
        $this->persistSystemLog(
            level: 'warning',
            message: $message,
            category: $category,
            operation: $operation,
            userId: $userId,
            context: $context,
            httpStatus: $httpStatus,
            processingStatus: $processingStatus,
        );
    }

    public function logSystemError(
        Throwable $throwable,
        string $category,
        string $operation,
        ?int $userId = null,
        ?int $httpStatus = 500,
    ): void {
        $this->persistSystemLog(
            level: 'error',
            message: $throwable->getMessage() !== '' ? $throwable->getMessage() : 'Erro sem mensagem.',
            category: $category,
            operation: $operation,
            userId: $userId,
            context: [
                'exception' => $throwable::class,
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
            ],
            httpStatus: $httpStatus,
            processingStatus: 'error',
            stackTraceSummary: $this->buildStackTraceSummary($throwable),
        );
    }

    public function logAudit(
        string $action,
        string $auditableType,
        ?int $auditableId,
        ?array $beforeData,
        ?array $afterData,
        ?int $userId,
        ?string $userRole,
    ): void {
        $request = request();

        AuditLog::query()->create([
            'request_id' => $this->requestId($request),
            'trace_id' => $this->traceId($request),
            'user_id' => $userId,
            'user_role' => $userRole,
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'before_data' => $this->sanitizeArray($beforeData),
            'after_data' => $this->sanitizeArray($afterData),
            'route' => $request?->path(),
            'method' => $request?->method(),
            'ip' => $request?->ip(),
            'created_at' => now(),
        ]);
    }

    private function persistSystemLog(
        string $level,
        string $message,
        string $category,
        string $operation,
        ?int $userId,
        ?array $context = null,
        ?int $httpStatus = null,
        ?string $processingStatus = null,
        ?string $stackTraceSummary = null,
    ): void {
        $request = request();

        SystemLog::query()->create([
            'request_id' => $this->requestId($request),
            'trace_id' => $this->traceId($request),
            'level' => $level,
            'category' => $category,
            'service' => 'api',
            'operation' => $operation,
            'route' => $request?->path(),
            'method' => $request?->method(),
            'user_id' => $userId,
            'ip' => $request?->ip(),
            'message' => $message,
            'context' => $this->sanitizeArray($context),
            'input_payload' => $this->safeInput($request),
            'output_payload' => null,
            'http_status' => $httpStatus,
            'processing_status' => $processingStatus,
            'stack_trace_summary' => $stackTraceSummary,
            'created_at' => now(),
        ]);
    }

    private function safeInput(?Request $request): ?array
    {
        if ($request === null) {
            return null;
        }

        $input = $request->all();

        $sanitized = $this->sanitizeArray($input);

        return $sanitized === [] ? null : $sanitized;
    }

    private function sanitizeArray(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'new_password_confirmation',
            'client_secret',
            'access_token',
            'refresh_token',
            'token',
            'authorization',
        ];

        $sanitized = [];

        foreach ($data as $key => $value) {
            $normalizedKey = is_string($key) ? mb_strtolower($key) : $key;

            if (is_string($normalizedKey) && in_array($normalizedKey, $sensitiveKeys, true)) {
                $sanitized[$key] = '***';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    private function requestId(?Request $request): ?string
    {
        return $request?->attributes->get('request_id');
    }

    private function traceId(?Request $request): ?string
    {
        return $request?->attributes->get('trace_id');
    }

    private function buildStackTraceSummary(Throwable $throwable): string
    {
        $summary = $throwable->getFile() . ':' . $throwable->getLine();

        return mb_substr($summary, 0, 1000);
    }
}