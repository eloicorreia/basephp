<?php

declare(strict_types=1);

use App\Exceptions\ApiException;
use App\Http\Middleware\EnsurePasswordChangedMiddleware;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureTenantAccessMiddleware;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RequestContextMiddleware;
use App\Http\Middleware\ResolveTenantMiddleware;
use App\Services\Logging\LogPersistenceService;
use App\Support\Http\ApiErrorFormatter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use App\Http\Middleware\ApiRequestLoggingMiddleware;

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
            ApiRequestLoggingMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            $status = 422;

            try {
                app(LogPersistenceService::class)->logSystemError(
                    throwable: $e,
                    category: 'validation',
                    operation: 'exception_handler',
                    userId: $request->user()?->id,
                    httpStatus: $status,
                );
            } catch (Throwable) {
            }

            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => ApiErrorFormatter::fromValidation($e->errors()),
            ], $status);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            $status = 401;

            try {
                app(LogPersistenceService::class)->logSystemError(
                    throwable: $e,
                    category: 'authentication',
                    operation: 'exception_handler',
                    userId: $request->user()?->id,
                    httpStatus: $status,
                );
            } catch (Throwable) {
            }

            return response()->json([
                'success' => false,
                'message' => 'Não autenticado.',
                'errors' => [],
            ], $status);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            $status = 403;

            try {
                app(LogPersistenceService::class)->logSystemError(
                    throwable: $e,
                    category: 'authorization',
                    operation: 'exception_handler',
                    userId: $request->user()?->id,
                    httpStatus: $status,
                );
            } catch (Throwable) {
            }

            return response()->json([
                'success' => false,
                'message' => 'Acesso negado.',
                'errors' => [],
            ], $status);
        });

        $exceptions->render(function (ApiException $e, Request $request) {
            $status = $e->statusCode();

            try {
                app(LogPersistenceService::class)->logSystemError(
                    throwable: $e,
                    category: 'business',
                    operation: 'exception_handler',
                    userId: $request->user()?->id,
                    httpStatus: $status,
                );
            } catch (Throwable) {
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], $status);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            $status = $e->getStatusCode();

            $message = match ($status) {
                400 => $e->getMessage() !== '' ? $e->getMessage() : 'Requisição inválida.',
                401 => 'Não autenticado.',
                403 => 'Acesso negado.',
                404 => 'Recurso não encontrado.',
                405 => 'Método não permitido.',
                429 => 'Muitas requisições. Tente novamente em instantes.',
                default => $status >= 500
                    ? 'Erro ao processar a requisição.'
                    : ($e->getMessage() !== '' ? $e->getMessage() : 'Erro ao processar a requisição.'),
            };

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
                'message' => $message,
                'errors' => [],
            ], $status);
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            $status = 500;

            try {
                app(LogPersistenceService::class)->logSystemError(
                    throwable: $e,
                    category: 'system',
                    operation: 'exception_handler',
                    userId: $request->user()?->id,
                    httpStatus: $status,
                );
            } catch (Throwable) {
            }

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar a requisição.',
                'errors' => [],
            ], $status);
        });
    })
    ->create();