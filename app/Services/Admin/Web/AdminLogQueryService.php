<?php

declare(strict_types=1);

namespace App\Services\Admin\Web;

use App\Models\ApiRequestLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Throwable;

final readonly class AdminLogQueryService
{
    public function __construct(
        private VisibleLogSanitizer $visibleLogSanitizer
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{date_from: string, date_to: string, method: ?string, status: ?string, search: ?string, per_page: int}
     */
    public function normalizeApiRequestFilters(array $filters = [], ?int $perPage = null): array
    {
        $defaultPeriodDays = max(1, (int) config('admin_web.logs.default_period_days', 1));
        $maxPeriodDays = max($defaultPeriodDays, (int) config('admin_web.logs.max_period_days', 31));
        $maxPerPage = max(5, (int) config('admin_web.logs.max_per_page', 50));

        $dateTo = $this->dateFilter($filters['date_to'] ?? null) ?? now();
        $dateFrom = $this->dateFilter($filters['date_from'] ?? null) ?? $dateTo->copy()->subDays($defaultPeriodDays - 1);

        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        if ($dateFrom->diffInDays($dateTo) >= $maxPeriodDays) {
            $dateFrom = $dateTo->copy()->subDays($maxPeriodDays - 1);
        }

        return [
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'method' => $this->stringFilter($filters['method'] ?? null),
            'status' => $this->stringFilter($filters['status'] ?? null),
            'search' => $this->stringFilter($filters['search'] ?? null),
            'per_page' => max(5, min($perPage ?? (int) ($filters['per_page'] ?? config('admin_web.logs.per_page', 15)), $maxPerPage)),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateApiRequestLogs(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters = $this->normalizeApiRequestFilters($filters, $perPage);

        $query = ApiRequestLog::query()
            ->latest('created_at')
            ->latest('id');

        $this->applyApiRequestFilters($query, $filters);

        return $query
            ->paginate((int) $filters['per_page'])
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
            'message' => $this->visibleLogSanitizer->sanitizeText(
                text: $log->message,
                maxLength: (int) config('admin_web.logs.preview_text_limit', 1000),
            ),
            'user_agent' => $this->visibleLogSanitizer->sanitizeText(
                text: $log->user_agent,
                maxLength: (int) config('admin_web.logs.preview_text_limit', 1000),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function apiRequestPayload(ApiRequestLog $log): array
    {
        $detailLimit = (int) config('admin_web.logs.detail_text_limit', 4000);

        return [
            ...$this->apiRequestDetail($log),
            'request_headers' => $this->visibleLogSanitizer->sanitizePayload($log->request_headers, $detailLimit),
            'request_query' => $this->visibleLogSanitizer->sanitizePayload($log->request_query, $detailLimit),
            'request_body' => $this->visibleLogSanitizer->sanitizePayload($log->request_body, $detailLimit),
            'response_body' => $this->visibleLogSanitizer->sanitizePayload($log->response_body, $detailLimit),
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
        $dateFrom = Carbon::parse((string) $filters['date_from'])->startOfDay();
        $dateTo = Carbon::parse((string) $filters['date_to'])->endOfDay();

        $query->whereBetween('created_at', [$dateFrom, $dateTo]);

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

    private function dateFilter(mixed $value): ?Carbon
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
