<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\Auth\AdminLoginRequest;
use App\Models\User;
use App\Services\Admin\Web\AdminWebAuditService;
use App\Services\Auth\WebAdminLoginService;
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

    public function store(AdminLoginRequest $request, AdminWebAuditService $adminWebAuditService, WebAdminLoginService $loginService): RedirectResponse
    {
        $result = $loginService->attempt($request, $adminWebAuditService);

        if (! $result->successful) {
            return back()
                ->withErrors(['email' => $result->errorMessage ?? 'Credenciais inválidas.'])
                ->onlyInput('email');
        }

        if ($result->mustChangePassword) {
            return redirect()->route('admin.password.change');
        }

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
