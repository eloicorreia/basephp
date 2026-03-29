<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\Admin\CreateUserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Admin\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->with('role')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Dados recuperados com sucesso.',
            'data' => UserResource::collection($users->items()),
            'meta' => [
                'page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $dto = CreateUserDTO::fromArray($request->validated());
        $user = $this->userService->create($dto)->load('role');

        return response()->json([
            'success' => true,
            'message' => 'Usuário criado com sucesso.',
            'data' => new UserResource($user),
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        $user->load('role');

        return response()->json([
            'success' => true,
            'message' => 'Dados recuperados com sucesso.',
            'data' => new UserResource($user),
        ]);
    }
}