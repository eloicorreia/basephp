<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

class IssueTokenTest extends TestCase
{
    use BuildsAuthTenancyFixtures;
    use RefreshDatabase;

    public function test_it_issues_a_password_grant_token(): void
    {
        $user = User::factory()->create([
            'email' => 'adminnfe@local.test',
            'password' => bcrypt('adminnfe'),
        ]);

        $client = app(ClientRepository::class)->createPasswordGrantClient(
            'Test Password Client',
            'users'
        );

        $response = $this->post('/oauth/token', [
            'grant_type' => 'password',
            'client_id' => $client->getKey(),
            'client_secret' => $client->secret,
            'username' => $user->email,
            'password' => 'adminnfe',
            'scope' => 'user.profile tenant.access',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token_type',
                'expires_in',
                'access_token',
                'refresh_token',
            ]);
    }

    public function test_it_rejects_invalid_password_for_password_grant(): void
    {
        $user = User::factory()->create([
            'email' => 'adminnfe@local.test',
            'password' => bcrypt('adminnfe'),
        ]);

        $client = app(ClientRepository::class)->createPasswordGrantClient(
            'Test Password Client',
            'users'
        );

        $response = $this->post('/oauth/token', [
            'grant_type' => 'password',
            'client_id' => $client->getKey(),
            'client_secret' => $client->secret,
            'username' => $user->email,
            'password' => 'senha-incorreta',
            'scope' => 'user.profile tenant.access',
        ]);

        $response->assertStatus(400)
            ->assertJsonStructure([
                'error',
                'error_description',
            ])
            ->assertJson([
                'error' => 'invalid_grant',
            ]);

        $this->assertIsString($response->json('error_description'));
        $this->assertNotSame('', trim((string) $response->json('error_description')));
    }

    public function test_protected_route_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => 'tenant-main',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Não autenticado.',
                'errors' => [],
            ]);
    }
}