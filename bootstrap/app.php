<?php

declare(strict_types=1);

use App\Http\Middleware\EnsurePasswordChangedMiddleware;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureTenantAccessMiddleware;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RequestContextMiddleware;
use App\Http\Middleware\ResolveTenantMiddleware;
use App\Services\Logging\LogPersistenceService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'user.active' => EnsureUserIsActive::class,
            'tenant.resolve' => ResolveTenantMiddleware::class,
            'tenant.access' => EnsureTenantAccessMiddleware::class,
            'password.changed' => EnsurePasswordChangedMiddleware::class,
        ]);

        $middleware->appendToGroup('api', [
            RequestContextMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            $status = 500;

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
            }

            try {
                app(LogPersistenceService::class)->logSystemError(
                    throwable: $e,
                    category: 'http',
                    operation: 'exception_handler',
                    userId: $request->user()?->id,
                    httpStatus: $status,
                );
            } catch (Throwable) {
            }

            return response()->json([
                'success' => false,
                'message' => $status >= 500
                    ? 'Erro ao processar a requisição.'
                    : ($e->getMessage() ?: 'Erro ao processar a requisição.'),
                'errors' => [],
            ], $status);
        });
    })
    ->create();