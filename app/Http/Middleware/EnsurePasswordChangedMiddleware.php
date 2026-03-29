<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChangedMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Não autenticado.');
        }

        $currentPath = trim($request->path(), '/');

        if ($user->must_change_password && $currentPath !== 'api/v1/auth/change-password') {
            abort(403, 'É obrigatório alterar a senha antes de continuar.');
        }

        return $next($request);
    }
}