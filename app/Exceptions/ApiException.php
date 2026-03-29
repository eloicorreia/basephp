<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestContextMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-Id', (string) Str::uuid());
        $traceId = $request->header('X-Trace-Id', (string) Str::uuid());

        $request->attributes->set('request_id', $requestId);
        $request->attributes->set('trace_id', $traceId);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Request-Id', $requestId);
        $response->headers->set('X-Trace-Id', $traceId);

        return $response;
    }
}