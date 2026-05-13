<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccessMiddleware
{
    public function __construct(
        private readonly TenantContext $tenantContext
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Não autenticado.');
        }

        $tenant = $this->tenantContext->get();

        if ($tenant === null) {
            abort(404, 'Tenant não encontrado.');
        }

        $hasAccess = $user->tenantUsers()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->exists();

        if (! $hasAccess) {
            abort(403, 'Usuário sem acesso ao tenant informado.');
        }

        return $next($request);
    }
}
