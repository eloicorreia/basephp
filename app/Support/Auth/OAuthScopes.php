<?php

declare(strict_types=1);

namespace App\Support\Auth;

final class OAuthScopes
{
    public const ADMIN_FULL = 'admin.full';

    public const TENANT_ACCESS = 'tenant.access';

    public const USER_PROFILE = 'user.profile';

    public const USER_PASSWORD_CHANGE = 'user.password.change';

    public const TENANT_USERS_READ = 'tenant.users.read';

    public const TENANT_USERS_WRITE = 'tenant.users.write';

    public const TENANTS_READ = 'tenants.read';

    public const TENANTS_WRITE = 'tenants.write';

    public const USERS_READ = 'users.read';

    public const USERS_WRITE = 'users.write';

    public const ROLES_READ = 'roles.read';

    public const ROLES_WRITE = 'roles.write';

    public const PERMISSIONS_READ = 'permissions.read';

    public const PERMISSIONS_WRITE = 'permissions.write';

    public const QUEUES_READ = 'queues.read';

    public const QUEUES_WRITE = 'queues.write';

    public const EMAILS_READ = 'emails.read';

    public const EMAILS_WRITE = 'emails.write';

    public const SYSTEM_HEALTH = 'system.health';

    /**
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return [
            self::ADMIN_FULL => 'Acesso administrativo total',
            self::TENANT_ACCESS => 'Acesso ao tenant autenticado',
            self::USER_PROFILE => 'Acesso ao perfil autenticado',
            self::USER_PASSWORD_CHANGE => 'Alteração da própria senha',
            self::TENANT_USERS_READ => 'Listagem de usuários do tenant',
            self::TENANT_USERS_WRITE => 'Criação e manutenção de usuários do tenant',
            self::TENANTS_READ => 'Listagem de tenants',
            self::TENANTS_WRITE => 'Criação e manutenção de tenants',
            self::USERS_READ => 'Listagem de usuários',
            self::USERS_WRITE => 'Criação e manutenção de usuários',
            self::ROLES_READ => 'Listagem de roles e permissões vinculadas',
            self::ROLES_WRITE => 'Manutenção de permissões vinculadas a roles',
            self::PERMISSIONS_READ => 'Listagem de permissões',
            self::PERMISSIONS_WRITE => 'Manutenção de permissões',
            self::QUEUES_READ => 'Consulta operacional de filas',
            self::QUEUES_WRITE => 'Ações operacionais em filas',
            self::EMAILS_READ => 'Consulta de envios de e-mail',
            self::EMAILS_WRITE => 'Envio e reprocessamento de e-mails',
            self::SYSTEM_HEALTH => 'Verificação operacional sistema-a-sistema',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function authorizationCodeDescriptions(): array
    {
        $descriptions = self::descriptions();

        unset($descriptions[self::SYSTEM_HEALTH]);

        return $descriptions;
    }

    /**
     * @return array<string, string>
     */
    public static function clientCredentialsDescriptions(): array
    {
        return [
            self::SYSTEM_HEALTH => self::descriptions()[self::SYSTEM_HEALTH],
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::descriptions());
    }

    public static function scope(string $scope): string
    {
        return 'scope:'.$scope;
    }

    public static function any(string ...$scopes): string
    {
        return 'any_scope:'.implode(',', $scopes);
    }
}
