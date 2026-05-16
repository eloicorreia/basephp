<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401, 'Não autenticado.');
        }

        if (! $user->hasPermission($permission)) {
            abort(403, 'Acesso negado.');
        }

        return $next($request);
    }
}
