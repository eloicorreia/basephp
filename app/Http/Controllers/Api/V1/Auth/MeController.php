<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthenticatedUserResource;
use App\Services\Logging\LogPersistenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __construct(
        private readonly LogPersistenceService $logPersistenceService
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user()?->load([
            'role',
            'tenantUsers.tenant',
            'tenantUsers.role',
        ]);

        $this->logPersistenceService->logSystemInfo(
            message: 'Consulta de usuário autenticado realizada com sucesso.',
            category: 'auth',
            operation: 'me',
            userId: $user?->id,
            httpStatus: 200,
            processingStatus: 'success',
        );

        return response()->json([
            'success' => true,
            'message' => 'Dados recuperados com sucesso.',
            'data' => new AuthenticatedUserResource($user),
        ]);
    }
}