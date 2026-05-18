<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\TenantSettings\TenantRuntimeSettings;
use App\Support\Http\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApplyTenantRuntimeSettings
{
    public function __construct(
        private readonly TenantRuntimeSettings $runtimeSettings,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $previousAppTimezone = config('app.timezone');
        $previousPhpTimezone = date_default_timezone_get();
        $previousLocale = app()->getLocale();
        $settings = $this->runtimeSettings->settings();

        try {
            config([
                'app.timezone' => $this->runtimeSettings->timezone(),
                'tenant.runtime.timezone' => $this->runtimeSettings->timezone(),
                'tenant.runtime.locale' => $this->runtimeSettings->locale(),
                'tenant.runtime.date_format' => $this->runtimeSettings->dateFormat(),
                'tenant.runtime.datetime_format' => $this->runtimeSettings->datetimeFormat(),
                'tenant.runtime.default_items_per_page' => $this->runtimeSettings->defaultPerPage(),
                'tenant.runtime.max_items_per_page' => $this->runtimeSettings->maxPerPage(),
                'tenant.runtime.support_email' => $settings->support_email,
                'tenant.runtime.support_phone' => $settings->support_phone,
            ]);
            date_default_timezone_set($this->runtimeSettings->timezone());
            app()->setLocale($this->runtimeSettings->locale());
            $this->applyPaginationDefaults($request);

            if ((bool) $settings->maintenance_mode) {
                return ApiResponse::error(
                    message: 'Sistema em manutenção.',
                    errors: [[
                        'field' => 'maintenance_message',
                        'message' => (string) ($settings->maintenance_message ?: 'O tenant está temporariamente em manutenção.'),
                        'type' => 'MAINTENANCE_MODE',
                    ]],
                    status: 503,
                );
            }

            return $next($request);
        } finally {
            config(['app.timezone' => $previousAppTimezone]);
            date_default_timezone_set($previousPhpTimezone);
            app()->setLocale($previousLocale);
        }
    }

    private function applyPaginationDefaults(Request $request): void
    {
        $defaultPerPage = $this->runtimeSettings->defaultPerPage();
        $maxPerPage = $this->runtimeSettings->maxPerPage();

        if (! $request->query->has('per_page')) {
            $request->query->set('per_page', (string) $defaultPerPage);

            return;
        }

        $perPage = $request->integer('per_page', $defaultPerPage);
        $request->query->set('per_page', (string) max(1, min($perPage, $maxPerPage)));
    }
}
