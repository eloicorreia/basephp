<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Não autenticado.');
        }

        $tenantCode = $request->header('X-Tenant-Id');

        $tenant = Tenant::query()
            ->where('code', $tenantCode)
            ->where('status', 'active')
            ->first();

        if ($tenant === null) {
            abort(404, 'Tenant não encontrado.');
        }

        $hasAccess = $user->tenantUsers()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'Usuário sem acesso ao tenant informado.');
        }

        return $next($request);
    }
}