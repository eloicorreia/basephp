<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Não autenticado.');
        }

        if (!$user->is_active) {
            abort(403, 'Usuário inativo.');
        }

        return $next($request);
    }
}