<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\DTO\Admin\AssignUserRoleDTO;
use App\Models\Role;
use App\Services\Admin\UserService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminUserRoleAssignmentConcurrencyTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_admin_role_assignment_waits_for_existing_admin_locks(): void
    {
        $adminRole = Role::query()->firstOrCreate(
            ['code' => 'admin'],
            [
                'name' => 'Administrator',
                'active' => true,
            ]
        );
        $targetRole = $this->createRole(
            'concurrency-target-role-'.str_replace('-', '', (string) Str::uuid()),
            'Concurrency Target Role'
        );

        $actor = $this->createUser(role: $adminRole);
        $targetAdmin = $this->createUser(role: $adminRole);

        Passport::actingAs($actor, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

        $lockConnectionName = $this->configureLockHolderConnection();
        $lockConnection = DB::connection($lockConnectionName);

        $lockConnection->beginTransaction();

        try {
            $lockConnection->select(
                'select id from users where is_active = true and role_id = ? for update',
                [$adminRole->id]
            );

            DB::statement("set lock_timeout = '250ms'");

            $exception = null;

            try {
                app(UserService::class)->assignRole(
                    $targetAdmin,
                    new AssignUserRoleDTO($targetRole->id)
                );
            } catch (QueryException $queryException) {
                $exception = $queryException;
            }

            $this->assertInstanceOf(QueryException::class, $exception);
            $this->assertSame('55P03', (string) $exception->getCode());
        } finally {
            DB::statement('reset lock_timeout');
            $lockConnection->rollBack();
            DB::disconnect($lockConnectionName);
        }
    }

    private function configureLockHolderConnection(): string
    {
        $connectionName = 'pgsql_lock_holder';

        config([
            'database.connections.'.$connectionName => config('database.connections.pgsql'),
        ]);

        DB::purge($connectionName);

        return $connectionName;
    }
}
