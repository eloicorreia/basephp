<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Logging\ApiRequestLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApiRequestLoggingMiddleware
{
    public function __construct(
        private readonly ApiRequestLogger $apiRequestLogger
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        try {
            /** @var Response $response */
            $response = $next($request);

            $this->apiRequestLogger->log(
                request: $request,
                response: $response,
                durationMs: (int) ((microtime(true) - $start) * 1000),
                status: $this->processingStatusFor($response),
            );

            return $response;
        } catch (Throwable $throwable) {
            throw $throwable;
        }
    }

    private function processingStatusFor(Response $response): string
    {
        if ($response->getStatusCode() >= 500) {
            return 'ERROR';
        }

        if ($response->getStatusCode() >= 400) {
            return 'FAILED';
        }

        return 'SUCCESS';
    }
}
