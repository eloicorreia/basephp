<?php

declare(strict_types=1);

namespace App\Services\Web;

use App\Models\AdminMenuEventLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

final class AdminMenuEventLogger
{
    private const SENSITIVE_KEYS = [
        'password',
        'token',
        'secret',
        'authorization',
        'client_secret',
        'refresh_token',
        'access_token',
    ];

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>|null  $context
     */
    public function log(
        string $event,
        string $level = 'info',
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $context = null,
        ?string $message = null,
        ?Throwable $throwable = null
    ): void {
        try {
            $request = request();
            $user = auth()->user();
            $userAgent = $request->userAgent();

            AdminMenuEventLog::query()->create([
                'occurred_at' => now(),
                'event' => $event,
                'level' => $level,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'user_id' => $user instanceof User ? $user->id : null,
                'request_id' => $request->attributes->get('request_id', $request->header('X-Request-Id')),
                'trace_id' => $request->attributes->get('trace_id', $request->header('X-Trace-Id')),
                'ip_address' => $request->ip(),
                'user_agent' => is_string($userAgent) ? mb_substr($userAgent, 0, 500) : null,
                'old_values' => $this->sanitizeArray($oldValues),
                'new_values' => $this->sanitizeArray($newValues),
                'context' => $this->sanitizeArray($context),
                'message' => $message,
                'stack_summary' => $throwable instanceof Throwable ? $this->stackSummary($throwable) : null,
            ]);
        } catch (Throwable $loggingFailure) {
            Log::warning('Falha ao persistir evento técnico do menu administrativo.', [
                'event' => $event,
                'exception' => $loggingFailure::class,
                'message' => $loggingFailure->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<mixed>|null  $data
     * @return array<mixed>|null
     */
    private function sanitizeArray(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $sanitized = [];

        foreach ($data as $key => $value) {
            $normalizedKey = is_string($key) ? strtolower($key) : $key;

            if (is_string($normalizedKey) && in_array($normalizedKey, self::SENSITIVE_KEYS, true)) {
                $sanitized[$key] = '[masked]';

                continue;
            }

            $sanitized[$key] = is_array($value) ? $this->sanitizeArray($value) : $value;
        }

        return $sanitized;
    }

    private function stackSummary(Throwable $throwable): string
    {
        return mb_substr(sprintf(
            '%s: %s in %s:%d',
            $throwable::class,
            $throwable->getMessage(),
            $throwable->getFile(),
            $throwable->getLine(),
        ), 0, 1000);
    }
}
