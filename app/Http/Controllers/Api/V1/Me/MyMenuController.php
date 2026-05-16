<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Web\AdminMenuBuilderService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MyMenuController extends Controller
{
    public function __construct(
        private readonly AdminMenuBuilderService $adminMenuBuilderService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return ApiResponse::error(
                message: 'Usuário não autenticado.',
                status: 401,
            );
        }

        return ApiResponse::success(
            data: $this->adminMenuBuilderService->buildForUser($user),
            message: 'Menu recuperado com sucesso.',
        );
    }
}
