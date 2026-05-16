<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Support\Web\WebAdminPermissions;

final class PermissionRegistry
{
    public const ADMIN_FULL = 'admin.full';

    public const TENANTS_READ = 'tenants.read';

    public const TENANTS_WRITE = 'tenants.write';

    public const USERS_READ = 'users.read';

    public const USERS_WRITE = 'users.write';

    public const ROLES_READ = 'roles.read';

    public const ROLES_WRITE = 'roles.write';

    public const PERMISSIONS_READ = 'permissions.read';

    public const PERMISSIONS_WRITE = 'permissions.write';

    public const TENANT_USERS_READ = 'tenant.users.read';

    public const TENANT_USERS_WRITE = 'tenant.users.write';

    public const QUEUES_READ = 'queues.read';

    public const QUEUES_WRITE = 'queues.write';

    public const EMAILS_READ = 'emails.read';

    public const EMAILS_WRITE = 'emails.write';

    /**
     * @return array<string, array{name: string, description: string, context: string, is_sensitive: bool}>
     */
    public static function definitions(): array
    {
        return [
            self::ADMIN_FULL => [
                'name' => 'Acesso administrativo total',
                'description' => 'Permite executar todas as ações administrativas da API e do painel web.',
                'context' => 'both',
                'is_sensitive' => true,
            ],
            self::TENANTS_READ => self::api('Consultar tenants', 'Permite consultar tenants.'),
            self::TENANTS_WRITE => self::api('Manter tenants', 'Permite criar e manter tenants.', true),
            self::USERS_READ => self::api('Consultar usuários', 'Permite consultar usuários globais.'),
            self::USERS_WRITE => self::api('Manter usuários', 'Permite criar usuários e trocar roles.', true),
            self::ROLES_READ => self::api('Consultar roles', 'Permite consultar roles e suas permissões.'),
            self::ROLES_WRITE => self::api('Manter permissões de roles', 'Permite alterar permissões vinculadas a roles.', true),
            self::PERMISSIONS_READ => self::api('Consultar permissões', 'Permite consultar catálogo de permissões.'),
            self::PERMISSIONS_WRITE => self::api('Manter permissões', 'Permite manter permissões do catálogo.', true),
            self::TENANT_USERS_READ => self::api('Consultar vínculos tenant usuário', 'Permite consultar vínculos usuário x tenant.'),
            self::TENANT_USERS_WRITE => self::api('Manter vínculos tenant usuário', 'Permite criar e manter vínculos usuário x tenant.', true),
            self::QUEUES_READ => self::api('Consultar filas', 'Permite consultar filas e jobs.'),
            self::QUEUES_WRITE => self::api('Operar filas', 'Permite executar ações operacionais de filas.', true),
            self::EMAILS_READ => self::api('Consultar e-mails', 'Permite consultar envios de e-mail.'),
            self::EMAILS_WRITE => self::api('Operar e-mails', 'Permite enviar e reprocessar e-mails.', true),
            WebAdminPermissions::ACCESS => self::web('Acessar painel web', 'Permite acessar o módulo web administrativo.', true),
            WebAdminPermissions::DASHBOARD_VIEW => self::web('Visualizar dashboard', 'Permite visualizar o dashboard administrativo.'),
            WebAdminPermissions::API_REQUEST_LOGS_VIEW => self::web('Consultar logs de API', 'Permite consultar logs de requisições da API.'),
            WebAdminPermissions::API_REQUEST_LOG_PAYLOADS_VIEW => self::web('Visualizar payloads de logs', 'Permite visualizar payloads mascarados dos logs da API.', true),
        ];
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * @return array{name: string, description: string, context: string, is_sensitive: bool}
     */
    private static function api(string $name, string $description, bool $sensitive = false): array
    {
        return [
            'name' => $name,
            'description' => $description,
            'context' => 'api',
            'is_sensitive' => $sensitive,
        ];
    }

    /**
     * @return array{name: string, description: string, context: string, is_sensitive: bool}
     */
    private static function web(string $name, string $description, bool $sensitive = false): array
    {
        return [
            'name' => $name,
            'description' => $description,
            'context' => 'web',
            'is_sensitive' => $sensitive,
        ];
    }
}
