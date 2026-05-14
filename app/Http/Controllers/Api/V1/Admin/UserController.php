<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTO\Admin\CreateUserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Admin\UserService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->with('role')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return ApiResponse::paginated(
            paginator: $users,
            data: UserResource::collection($users->items()),
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $dto = CreateUserDTO::fromArray($request->validated());
        $user = $this->userService->create($dto)->load('role');

        return ApiResponse::success(
            data: new UserResource($user),
            message: 'Usuário criado com sucesso.',
            status: 201,
        );
    }

    public function show(User $user): JsonResponse
    {
        $user->load('role');

        return ApiResponse::retrieved(new UserResource($user));
    }
}
