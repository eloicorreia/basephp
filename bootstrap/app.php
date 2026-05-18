<?php

declare(strict_types=1);

use App\Console\Commands\SystemUpdateCommand;
use App\Console\Commands\TenantsMigrateCommand;
use App\Console\Commands\TenantsProvisionCommand;
use App\Console\Commands\TenantsValidateCommand;
use App\Exceptions\ApiException;
use App\Http\Middleware\ApiRequestLoggingMiddleware;
use App\Http\Middleware\ApplyTenantRuntimeSettings;
use App\Http\Middleware\ApplyTenantSecuritySettings;
use App\Http\Middleware\EnsureClientCredentials;
use App\Http\Middleware\EnsurePasswordChangedMiddleware;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureTenantAccessMiddleware;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureWebAdminPermission;
use App\Http\Middleware\RequestContextMiddleware;
use App\Http\Middleware\ResolveTenantMiddleware;
use App\Services\Logging\LogPersistenceService;
use App\Support\Http\ApiErrorFormatter;
use App\Support\Http\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Http\Middleware\CheckToken;
use Laravel\Passport\Http\Middleware\CheckTokenForAnyScope;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        TenantsMigrateCommand::class,
        TenantsValidateCommand::class,
        TenantsProvisionCommand::class,
        SystemUpdateCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => EnsurePermission::class,
            'role' => EnsureRole::class,
            'user.active' => EnsureUserIsActive::class,
            'tenant.resolve' => ResolveTenantMiddleware::class,
            'tenant.runtime-settings' => ApplyTenantRuntimeSettings::class,
            'tenant.security-settings' => ApplyTenantSecuritySettings::class,
            'tenant.access' => EnsureTenantAccessMiddleware::class,
            'password.changed' => EnsurePasswordChangedMiddleware::class,
            'client.credentials' => EnsureClientCredentials::class,
            'web.permission' => EnsureWebAdminPermission::class,
            'scope' => CheckToken::class,
            'scopes' => CheckToken::class,
            'any_scope' => CheckTokenForAnyScope::class,
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

            return ApiResponse::error(
                message: 'Erro de validação.',
                errors: ApiErrorFormatter::fromValidation($e->errors()),
                status: $status,
            );
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

            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return redirect()->guest('/admin/login');
            }

            return ApiResponse::error(
                message: 'Usuário não autenticado.',
                status: $status,
            );
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

            return ApiResponse::error(
                message: 'Acesso negado.',
                status: $status,
            );
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

            return ApiResponse::error(
                message: $e->getMessage(),
                errors: $e->errors(),
                status: $status,
            );
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

            return ApiResponse::error(
                message: $message,
                status: $status,
            );
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

            return ApiResponse::error(
                message: 'Erro ao processar a requisição.',
                status: $status,
            );
        });
    })
    ->create();
