<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\QueueListRequest;
use App\Http\Resources\Api\V1\QueueCatalogResource;
use App\Http\Resources\Api\V1\QueueJobResource;
use App\Http\Resources\Api\V1\QueueSummaryResource;
use App\Models\Job;
use App\Services\Logging\LogPersistenceService;
use App\Services\Queue\QueueCatalogService;
use App\Services\Queue\QueueMonitoringService;
use App\Support\Auth\AuthenticatedUserId;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

final class QueueController extends Controller
{
    public function __construct(
        private readonly QueueCatalogService $queueCatalogService,
        private readonly QueueMonitoringService $queueMonitoringService,
        private readonly LogPersistenceService $logPersistenceService,
    ) {}

    public function catalog(): JsonResponse
    {
        $items = $this->queueCatalogService->all();

        $this->logPersistenceService->logSystemInfo(
            message: 'Catálogo de filas consultado com sucesso.',
            category: 'queue',
            operation: 'catalog',
            userId: AuthenticatedUserId::resolve(),
            httpStatus: 200,
            processingStatus: 'success',
        );

        return ApiResponse::retrieved(QueueCatalogResource::collection($items));
    }

    public function summary(QueueListRequest $request): JsonResponse
    {
        $summary = $this->queueMonitoringService->summary(
            queue: $request->validated('queue'),
        );

        $this->logPersistenceService->logSystemInfo(
            message: 'Resumo de filas consultado com sucesso.',
            category: 'queue',
            operation: 'summary',
            userId: AuthenticatedUserId::resolve(),
            context: [
                'queue' => $request->validated('queue'),
            ],
            httpStatus: 200,
            processingStatus: 'success',
        );

        return ApiResponse::retrieved(new QueueSummaryResource($summary));
    }

    public function index(QueueListRequest $request): JsonResponse
    {
        $jobs = $this->queueMonitoringService->listJobs(
            queue: $request->validated('queue'),
            perPage: (int) $request->validated('per_page', 15),
        );

        return ApiResponse::paginated(
            paginator: $jobs,
            data: QueueJobResource::collection($jobs->items()),
        );
    }

    public function show(Job $job): JsonResponse
    {
        $payload = $this->queueMonitoringService->detail($job);

        return ApiResponse::retrieved(new QueueJobResource($payload));
    }
}
