<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CorrelationIdMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) ($request->headers->get('X-Request-Id') ?: Str::uuid()->toString());
        $correlationId = (string) ($request->headers->get('X-Correlation-Id') ?: $requestId);

        $request->attributes->set('request_id', $requestId);
        $request->attributes->set('correlation_id', $correlationId);

        $response = $next($request);

        $response->headers->set('X-Request-Id', $requestId);
        $response->headers->set('X-Correlation-Id', $correlationId);

        return $response;
    }
}