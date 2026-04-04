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
use Illuminate\Http\JsonResponse;

final class QueueController extends Controller
{
    public function __construct(
        private readonly QueueCatalogService $queueCatalogService,
        private readonly QueueMonitoringService $queueMonitoringService,
        private readonly LogPersistenceService $logPersistenceService,
    ) {
    }

    public function catalog(): JsonResponse
    {
        $items = $this->queueCatalogService->all();

        $this->logPersistenceService->logSystemInfo(
            message: 'Catálogo de filas consultado com sucesso.',
            category: 'queue',
            operation: 'catalog',
            userId: auth()->id(),
            httpStatus: 200,
            processingStatus: 'success',
        );

        return response()->json([
            'success' => true,
            'message' => 'Dados recuperados com sucesso.',
            'data' => QueueCatalogResource::collection($items),
        ]);
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
            userId: auth()->id(),
            context: [
                'queue' => $request->validated('queue'),
            ],
            httpStatus: 200,
            processingStatus: 'success',
        );

        return response()->json([
            'success' => true,
            'message' => 'Dados recuperados com sucesso.',
            'data' => new QueueSummaryResource($summary),
        ]);
    }

    public function index(QueueListRequest $request): JsonResponse
    {
        $jobs = $this->queueMonitoringService->listJobs(
            queue: $request->validated('queue'),
            perPage: (int) $request->validated('per_page', 15),
        );

        return response()->json([
            'success' => true,
            'message' => 'Dados recuperados com sucesso.',
            'data' => QueueJobResource::collection($jobs->items()),
            'meta' => [
                'page' => $jobs->currentPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
                'last_page' => $jobs->lastPage(),
            ],
        ]);
    }

    public function show(Job $job): JsonResponse
    {
        $payload = $this->queueMonitoringService->detail($job);

        return response()->json([
            'success' => true,
            'message' => 'Dados recuperados com sucesso.',
            'data' => new QueueJobResource($payload),
        ]);
    }
}