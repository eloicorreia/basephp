<?php

declare(strict_types=1);

namespace App\Services\Logging;

use App\Models\AuditLog;
use App\Models\SystemLog;
use App\Support\Logging\SensitiveDataSanitizer;
use Illuminate\Http\Request;
use Throwable;

class LogPersistenceService
{
    public function __construct(
        private readonly SensitiveDataSanitizer $sensitiveDataSanitizer,
    ) {}

    /**
     * @param  array<string, mixed>|null  $context
     */
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

    /**
     * @param  array<string, mixed>|null  $context
     */
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
        $message = $throwable->getMessage() !== ''
            ? $throwable->getMessage()
            : 'Erro sem mensagem.';

        $this->persistSystemLog(
            level: 'error',
            message: $message,
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

    /**
     * @param  array<string, mixed>|null  $beforeData
     * @param  array<string, mixed>|null  $afterData
     */
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
            'route' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $context
     */
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
            'route' => $request->path(),
            'method' => $request->method(),
            'user_id' => $userId,
            'ip' => $request->ip(),
            'message' => $this->sanitizeText($message),
            'context' => $this->sanitizeArray($context),
            'input_payload' => $this->safeInput($request),
            'output_payload' => null,
            'http_status' => $httpStatus,
            'processing_status' => $processingStatus,
            'stack_trace_summary' => $stackTraceSummary !== null ? $this->sanitizeText($stackTraceSummary, 1000) : null,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<mixed>|null
     */
    private function safeInput(Request $request): ?array
    {
        $input = $request->all();
        $sanitized = $this->sanitizeArray($input);

        return $sanitized === [] ? null : $sanitized;
    }

    /**
     * @param  array<mixed>|null  $data
     * @return array<mixed>|null
     */
    private function sanitizeArray(?array $data): ?array
    {
        return $this->sensitiveDataSanitizer->sanitizeArray($data);
    }

    private function sanitizeText(string $value, int $maxLength = 4000): string
    {
        return $this->sensitiveDataSanitizer->sanitizeText($value, maxLength: $maxLength);
    }

    private function requestId(Request $request): ?string
    {
        return $request->attributes->get('request_id');
    }

    private function traceId(Request $request): ?string
    {
        return $request->attributes->get('trace_id');
    }

    private function buildStackTraceSummary(Throwable $throwable): string
    {
        $summary = $throwable->getFile().':'.$throwable->getLine();

        return mb_substr($summary, 0, 1000);
    }
}
