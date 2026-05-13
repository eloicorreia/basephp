<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class EnsurePasswordChangedMiddlewareTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['api', 'auth:api', 'password.changed'])
            ->prefix('api/v1/test/middleware')
            ->get('/password-changed', fn () => response()->json(['success' => true]));
    }

    public function test_it_blocks_user_that_must_change_password(): void
    {
        $user = $this->createUser(mustChangePassword: true);

        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

        $this->getJson('/api/v1/test/middleware/password-changed')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Acesso negado.',
                'errors' => [],
            ]);
    }

    public function test_it_allows_user_with_password_already_changed(): void
    {
        $user = $this->createUser(mustChangePassword: false);

        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

        $this->getJson('/api/v1/test/middleware/password-changed')
            ->assertOk()
            ->assertJson(['success' => true]);
    }
}
