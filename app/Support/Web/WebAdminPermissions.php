<?php

declare(strict_types=1);

namespace App\Support\Web;

use App\Models\User;

final class WebAdminPermissions
{
    public const ACCESS = 'admin.web.access';

    public const DASHBOARD_VIEW = 'admin.dashboard.view';

    public const API_REQUEST_LOGS_VIEW = 'admin.logs.api_requests.view';

    public const API_REQUEST_LOG_PAYLOADS_VIEW = 'admin.logs.api_requests.payloads.view';

    public const SECURITY_VIEW = 'admin.security.view';

    public const SECURITY_USERS_MANAGE = 'admin.security.users.manage';

    public const SECURITY_ROLES_MANAGE = 'admin.security.roles.manage';

    public const SECURITY_PERMISSIONS_MANAGE = 'admin.security.permissions.manage';

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
            self::SECURITY_VIEW => 'Visualização da árvore de segurança administrativa.',
            self::SECURITY_USERS_MANAGE => 'Cadastro e manutenção web de usuários administrativos.',
            self::SECURITY_ROLES_MANAGE => 'Cadastro e manutenção web de roles administrativas.',
            self::SECURITY_PERMISSIONS_MANAGE => 'Cadastro e manutenção web de permissões administrativas.',
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

        return $user->hasPermission($permission);
    }
}
