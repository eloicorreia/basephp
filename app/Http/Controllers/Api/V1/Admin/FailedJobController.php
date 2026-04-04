<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\FailedJobListRequest;
use App\Http\Resources\Api\V1\FailedJobResource;
use App\Models\FailedJob;
use App\Services\Queue\FailedJobService;
use Illuminate\Http\JsonResponse;

final class FailedJobController extends Controller
{
    public function __construct(
        private readonly FailedJobService $failedJobService,
    ) {
    }

    public function index(FailedJobListRequest $request): JsonResponse
    {
        $items = $this->failedJobService->list(
            queue: $request->validated('queue'),
            perPage: (int) $request->validated('per_page', 15),
        );

        return response()->json([
            'success' => true,
            'message' => 'Dados recuperados com sucesso.',
            'data' => FailedJobResource::collection($items->items()),
            'meta' => [
                'page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    public function show(FailedJob $failedJob): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Dados recuperados com sucesso.',
            'data' => new FailedJobResource($failedJob),
        ]);
    }

    public function retry(FailedJob $failedJob): JsonResponse
    {
        $this->failedJobService->retry(
            failedJob: $failedJob,
            userId: auth()->id(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Retry do job solicitado com sucesso.',
            'data' => [],
        ], 202);
    }

    public function destroy(FailedJob $failedJob): JsonResponse
    {
        $this->failedJobService->destroy(
            failedJob: $failedJob,
            userId: auth()->id(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Job falho removido com sucesso.',
            'data' => [],
        ]);
    }
}