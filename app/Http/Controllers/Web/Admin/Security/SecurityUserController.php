<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin\Security;

use App\DTO\Admin\CreateUserDTO;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\Security\StoreSecurityUserRequest;
use App\Http\Requests\Web\Admin\Security\UpdateSecurityUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Admin\Web\Security\SecurityUserManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SecurityUserController extends Controller
{
    public function __construct(
        private readonly SecurityUserManagementService $securityUserManagementService,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.security.users.index', [
            'users' => $this->securityUserManagementService->paginate(
                search: $request->filled('search') ? $request->string('search')->toString() : null,
                roleId: $request->filled('role_id') ? $request->integer('role_id') : null,
                status: $request->filled('status') ? $request->string('status')->toString() : null,
                perPage: $request->integer('per_page', 20),
            ),
            'roles' => Role::query()->orderBy('name')->get(),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'role_id' => $request->string('role_id')->toString(),
                'status' => $request->string('status')->toString(),
                'per_page' => $request->integer('per_page', 20),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.security.users.create', [
            'roles' => Role::query()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreSecurityUserRequest $request): RedirectResponse
    {
        $this->securityUserManagementService->create(CreateUserDTO::fromArray($request->validated()));

        return redirect()
            ->route('admin.security.users.index')
            ->with('status', 'Usuário criado com sucesso.');
    }

    public function edit(User $user): View
    {
        return view('admin.security.users.edit', [
            'managedUser' => $user->load('role'),
            'roles' => Role::query()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateSecurityUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        try {
            $this->securityUserManagementService->update(
                user: $user,
                name: (string) $data['name'],
                isActive: $request->boolean('is_active'),
                mustChangePassword: $request->boolean('must_change_password'),
            );
            $this->securityUserManagementService->assignRole($user, (int) $data['role_id']);
        } catch (ApiException $exception) {
            return back()->withErrors(['user' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('admin.security.users.edit', $user)
            ->with('status', 'Usuário atualizado com sucesso.');
    }
}
