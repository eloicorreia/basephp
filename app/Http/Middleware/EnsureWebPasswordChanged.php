<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureWebPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        if (! $user instanceof User) {
            abort(401, 'Não autenticado.');
        }

        if ((bool) $user->must_change_password) {
            return redirect()->route('admin.password.change');
        }

        return $next($request);
    }
}
