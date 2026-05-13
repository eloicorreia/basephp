<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class EnsureUserIsActiveMiddlewareTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['api', 'auth:api', 'user.active'])
            ->prefix('api/v1/test/middleware')
            ->get('/user-active', fn () => response()->json(['success' => true]));
    }

    public function test_it_blocks_inactive_user(): void
    {
        $user = $this->createUser(isActive: false);

        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

        $this->getJson('/api/v1/test/middleware/user-active')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Acesso negado.',
                'errors' => [],
            ]);
    }

    public function test_it_allows_active_user(): void
    {
        $user = $this->createUser(isActive: true);

        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

        $this->getJson('/api/v1/test/middleware/user-active')
            ->assertOk()
            ->assertJson(['success' => true]);
    }
}
