<?php

declare(strict_types=1);

namespace App\Services\Admin\Web;

use App\Models\ApiRequestLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final readonly class AdminLogQueryService
{
    public function __construct(
        private VisibleLogSanitizer $visibleLogSanitizer
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateApiRequestLogs(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(5, min($perPage, 50));

        $query = ApiRequestLog::query()
            ->latest('created_at')
            ->latest('id');

        $this->applyApiRequestFilters($query, $filters);

        return $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (ApiRequestLog $log): array => $this->apiRequestSummary($log));
    }

    /**
     * @return array<string, mixed>
     */
    public function apiRequestDetail(ApiRequestLog $log): array
    {
        return [
            ...$this->apiRequestSummary($log),
            'request_headers' => $this->visibleLogSanitizer->sanitizePayload($log->request_headers),
            'request_query' => $this->visibleLogSanitizer->sanitizePayload($log->request_query),
            'request_body' => $this->visibleLogSanitizer->sanitizePayload($log->request_body),
            'response_body' => $this->visibleLogSanitizer->sanitizePayload($log->response_body),
            'message' => $this->visibleLogSanitizer->sanitizeText($log->message),
            'user_agent' => $this->visibleLogSanitizer->sanitizeText($log->user_agent),
        ];
    }

    /**
     * @param  Builder<ApiRequestLog>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyApiRequestFilters(Builder $query, array $filters): void
    {
        $method = $this->stringFilter($filters['method'] ?? null);
        $status = $this->stringFilter($filters['status'] ?? null);
        $search = $this->stringFilter($filters['search'] ?? null);

        if ($method !== null) {
            $query->where('method', strtoupper($method));
        }

        if ($status !== null) {
            $query->where('processing_status', $status);
        }

        if ($search !== null) {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('request_id', 'like', '%'.$search.'%')
                    ->orWhere('trace_id', 'like', '%'.$search.'%')
                    ->orWhere('tenant_code', 'like', '%'.$search.'%')
                    ->orWhere('route', 'like', '%'.$search.'%')
                    ->orWhere('uri', 'like', '%'.$search.'%');
            });
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function apiRequestSummary(ApiRequestLog $log): array
    {
        return [
            'id' => $log->id,
            'request_id' => $log->request_id,
            'trace_id' => $log->trace_id,
            'tenant_code' => $log->tenant_code,
            'user_id' => $log->user_id,
            'oauth_client_id' => $log->oauth_client_id,
            'method' => $log->method,
            'route' => $log->route,
            'uri' => $this->visibleLogSanitizer->sanitizeText($log->uri),
            'http_status' => $log->http_status,
            'processing_status' => $log->processing_status,
            'duration_ms' => $log->duration_ms,
            'ip' => $log->ip,
            'created_at' => $log->created_at->format('Y-m-d H:i:s'),
        ];
    }

    private function stringFilter(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
