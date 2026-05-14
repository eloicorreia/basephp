<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Web\WebAdminPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureWebAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user('web');

        if (! $user instanceof User || ! WebAdminPermissions::allows($user, $permission)) {
            abort(403, 'Acesso negado.');
        }

        return $next($request);
    }
}
