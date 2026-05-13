<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class ClientCredentialsAuthorizationTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_password_grant_is_disabled_by_default(): void
    {
        $this->assertFalse(config('passport.enable_password_grant'));
        $this->assertFalse(Passport::$passwordGrantEnabled);
    }

    public function test_system_ping_allows_client_credentials_token_with_required_scope(): void
    {
        $client = Client::factory()->asClientCredentials()->create([
            'name' => 'System Health Client',
        ]);

        Passport::actingAsClient($client, ['system.health']);

        $this->getJson('/api/v1/system/ping')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['area' => 'system'],
            ]);
    }

    public function test_system_ping_rejects_client_credentials_token_without_required_scope(): void
    {
        $client = Client::factory()->asClientCredentials()->create([
            'name' => 'System Health Client Without Scope',
        ]);

        Passport::actingAsClient($client, []);

        $this->getJson('/api/v1/system/ping')
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Acesso negado.',
            ]);
    }

    public function test_system_ping_rejects_user_token_even_with_required_scope(): void
    {
        $user = $this->createUser();

        Passport::actingAs($user, ['system.health']);

        $this->getJson('/api/v1/system/ping')
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Acesso negado.',
            ]);
    }
}
