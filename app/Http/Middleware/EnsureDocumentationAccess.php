<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureDocumentationAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ((bool) config('l5-swagger.defaults.access.public', false)) {
            return $next($request);
        }

        if ($this->ipIsAllowed((string) $request->ip())) {
            return $next($request);
        }

        abort(404);
    }

    private function ipIsAllowed(string $ip): bool
    {
        $allowedIps = config('l5-swagger.defaults.access.allowed_ips', []);

        if (! is_array($allowedIps)) {
            return false;
        }

        return in_array($ip, $allowedIps, true);
    }
}
