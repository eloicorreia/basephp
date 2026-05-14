<?php

declare(strict_types=1);

namespace App\Support\Web;

use App\Enums\RoleCode;
use App\Models\User;

final class WebAdminPermissions
{
    public const ACCESS = 'admin.web.access';

    public const DASHBOARD_VIEW = 'admin.dashboard.view';

    public const API_REQUEST_LOGS_VIEW = 'admin.logs.api_requests.view';

    public const API_REQUEST_LOG_PAYLOADS_VIEW = 'admin.logs.api_requests.payloads.view';

    /**
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return [
            self::ACCESS => 'Acesso ao módulo web administrativo.',
            self::DASHBOARD_VIEW => 'Visualização do dashboard administrativo.',
            self::API_REQUEST_LOGS_VIEW => 'Consulta de logs de requisições da API.',
            self::API_REQUEST_LOG_PAYLOADS_VIEW => 'Visualização detalhada de payloads mascarados dos logs da API.',
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::descriptions());
    }

    public static function allows(User $user, string $permission): bool
    {
        if (! in_array($permission, self::all(), true)) {
            return false;
        }

        if (! $user->is_active) {
            return false;
        }

        $role = $user->role;

        if ($role === null || ! $role->active) {
            return false;
        }

        return $role->code === RoleCode::ADMIN->value;
    }
}
