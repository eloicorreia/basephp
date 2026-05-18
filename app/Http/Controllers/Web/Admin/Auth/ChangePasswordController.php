<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\Auth\AdminChangePasswordRequest;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ChangePasswordController extends Controller
{
    public function show(Request $request): View
    {
        return view('admin.auth.change-password', [
            'forced' => (bool) $request->user('web')?->must_change_password,
        ]);
    }

    public function update(AdminChangePasswordRequest $request, AuthService $authService): RedirectResponse
    {
        $user = $request->user('web');

        abort_unless($user instanceof User, 401);

        $authService->changePasswordForRequest(
            user: $user,
            currentPassword: (string) $request->validated('current_password'),
            newPassword: (string) $request->validated('new_password'),
            request: $request,
        );

        return redirect()
            ->intended(route('admin.dashboard'))
            ->with('status', 'Senha alterada com sucesso.');
    }
}
