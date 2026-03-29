<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ChangePasswordRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

class ChangePasswordController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    public function __invoke(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $this->authService->changePassword(
            user: $user,
            currentPassword: $request->validated('current_password'),
            newPassword: $request->validated('new_password'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Senha alterada com sucesso.',
            'data' => [],
        ]);
    }
}