<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\Auth\AdminLoginRequest;
use App\Models\User;
use App\Services\Admin\Web\AdminWebAuditService;
use App\Support\Web\WebAdminPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class LoginController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user('web') instanceof User) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function store(AdminLoginRequest $request, AdminWebAuditService $adminWebAuditService): RedirectResponse
    {
        $remember = $request->boolean('remember');
        $email = (string) $request->input('email');
        $candidateUser = User::query()->where('email', $email)->first();

        if (! Auth::guard('web')->attempt($request->only('email', 'password'), $remember)) {
            $adminWebAuditService->loginFailed($request, $candidateUser, $email);

            return back()
                ->withErrors(['email' => 'Credenciais inválidas.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = Auth::guard('web')->user();

        if (! $user instanceof User || ! WebAdminPermissions::allows($user, WebAdminPermissions::ACCESS)) {
            $adminWebAuditService->permissionDenied(
                request: $request,
                user: $user instanceof User ? $user : null,
                permission: WebAdminPermissions::ACCESS,
                reason: 'login_without_web_permission',
            );

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['email' => 'Usuário sem permissão para acessar o módulo administrativo.'])
                ->onlyInput('email');
        }

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $adminWebAuditService->loginSucceeded($request, $user);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request, AdminWebAuditService $adminWebAuditService): RedirectResponse
    {
        $user = $request->user('web');

        $adminWebAuditService->logout($request, $user instanceof User ? $user : null);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
