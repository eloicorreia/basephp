<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\RoleCode;
use App\Models\Permission;
use App\Support\Web\WebAdminPermissions;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class MyMenuEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_me_menu_returns_standard_json_for_authenticated_user(): void
    {
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role);

        Passport::actingAs($user, ['user.profile']);

        $this->getJson('/api/v1/me/menu')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Menu recuperado com sucesso.')
            ->assertJsonPath('data.0.code', 'painel')
            ->assertJsonPath('data.0.items.0.code', 'dashboard');
    }

    public function test_me_menu_requires_authenticated_user(): void
    {
        $this->getJson('/api/v1/me/menu')
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Usuário não autenticado.',
                'errors' => [],
            ]);
    }

    public function test_me_menu_hides_items_without_user_permission(): void
    {
        $role = $this->createRole('menu-api-user', 'Menu API User');
        $dashboardPermission = Permission::query()
            ->where('code', WebAdminPermissions::DASHBOARD_VIEW)
            ->firstOrFail();
        $role->permissions()->sync([$dashboardPermission->id => ['assigned_at' => now()]]);
        $user = $this->createUser(role: $role);

        Passport::actingAs($user, ['user.profile']);

        $this->getJson('/api/v1/me/menu')
            ->assertOk()
            ->assertJsonPath('data.0.items.0.code', 'dashboard')
            ->assertJsonMissing(['code' => 'api-request-logs']);
    }
}
