<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Não autenticado.');
        }

        if (!$user->hasRole(...$roles)) {
            abort(403, 'Acesso negado para este perfil.');
        }

        return $next($request);
    }
}