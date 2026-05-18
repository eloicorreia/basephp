<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\Auth\AdminResetPasswordRequest;
use App\Models\User;
use App\Services\Admin\Web\AdminWebAuditService;
use App\Support\Web\WebAdminPermissions;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

final class ResetPasswordController extends Controller
{
    public function show(Request $request, string $token): View
    {
        return view('admin.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function store(
        AdminResetPasswordRequest $request,
        AdminWebAuditService $adminWebAuditService
    ): RedirectResponse {
        $user = User::query()->where('email', (string) $request->validated('email'))->first();

        if (! $user instanceof User || ! WebAdminPermissions::allows($user, WebAdminPermissions::ACCESS)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Não foi possível redefinir a senha com os dados informados.']);
        }

        $resetUser = null;

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use (&$resetUser): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                    'must_change_password' => false,
                    'password_changed_at' => now(),
                ])->save();

                $resetUser = $user;

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET && $resetUser instanceof User) {
            $adminWebAuditService->passwordResetSucceeded($request, $resetUser);

            return redirect()
                ->route('login')
                ->with('status', 'Senha redefinida com sucesso. Acesse com a nova senha.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Não foi possível redefinir a senha com os dados informados.']);
    }
}
