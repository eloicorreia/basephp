<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\Auth\AdminForgotPasswordRequest;
use App\Models\User;
use App\Services\Admin\Web\AdminWebAuditService;
use App\Support\Web\WebAdminPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;

final class ForgotPasswordController extends Controller
{
    public function show(): View
    {
        return view('admin.auth.forgot-password');
    }

    public function store(
        AdminForgotPasswordRequest $request,
        AdminWebAuditService $adminWebAuditService
    ): RedirectResponse {
        $email = (string) $request->validated('email');
        $user = User::query()->where('email', $email)->first();
        $status = $user instanceof User && WebAdminPermissions::allows($user, WebAdminPermissions::ACCESS)
            ? Password::broker()->sendResetLink(['email' => $email])
            : Password::INVALID_USER;

        $adminWebAuditService->passwordResetRequested(
            request: $request,
            user: $user,
            email: $email,
            status: $status,
        );

        return back()->with('status', 'Se o e-mail estiver cadastrado, enviaremos as instruções de redefinição.');
    }
}
