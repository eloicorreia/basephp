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

    public const SYSTEM_SETTINGS_VIEW = 'admin.system-settings.view';

    public const SYSTEM_SETTINGS_GENERAL_MANAGE = 'admin.system-settings.general.manage';

    public const SYSTEM_SETTINGS_SECURITY_MANAGE = 'admin.system-settings.security.manage';

    public const SYSTEM_SETTINGS_PASSWORD_POLICY_MANAGE = 'admin.system-settings.password-policy.manage';

    public const SYSTEM_SETTINGS_MAIL_MANAGE = 'admin.system-settings.mail.manage';

    public const SYSTEM_SETTINGS_API_MANAGE = 'admin.system-settings.api.manage';

    public const SYSTEM_SETTINGS_QUEUE_MANAGE = 'admin.system-settings.queue.manage';

    public const SYSTEM_SETTINGS_AUDIT_MANAGE = 'admin.system-settings.audit.manage';

    public const SYSTEM_SETTINGS_INTEGRATION_MANAGE = 'admin.system-settings.integration.manage';

    public const SYSTEM_SETTINGS_WEBHOOK_MANAGE = 'admin.system-settings.webhook.manage';

    public const SYSTEM_SETTINGS_NOTIFICATION_MANAGE = 'admin.system-settings.notification.manage';

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
            self::SYSTEM_SETTINGS_VIEW => 'Visualização das configurações de sistema por tenant.',
            self::SYSTEM_SETTINGS_GENERAL_MANAGE => 'Manutenção das configurações gerais por tenant.',
            self::SYSTEM_SETTINGS_SECURITY_MANAGE => 'Manutenção das configurações de segurança por tenant.',
            self::SYSTEM_SETTINGS_PASSWORD_POLICY_MANAGE => 'Manutenção da política de senhas por tenant.',
            self::SYSTEM_SETTINGS_MAIL_MANAGE => 'Manutenção da configuração de e-mail por tenant.',
            self::SYSTEM_SETTINGS_API_MANAGE => 'Manutenção das configurações de API por tenant.',
            self::SYSTEM_SETTINGS_QUEUE_MANAGE => 'Manutenção das configurações de filas por tenant.',
            self::SYSTEM_SETTINGS_AUDIT_MANAGE => 'Manutenção das configurações de logs e auditoria por tenant.',
            self::SYSTEM_SETTINGS_INTEGRATION_MANAGE => 'Manutenção das configurações de integrações por tenant.',
            self::SYSTEM_SETTINGS_WEBHOOK_MANAGE => 'Manutenção das configurações de webhooks por tenant.',
            self::SYSTEM_SETTINGS_NOTIFICATION_MANAGE => 'Manutenção das configurações de notificações por tenant.',
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
