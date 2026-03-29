<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\TenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class IssueTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_issues_a_password_grant_token(): void
    {
        $this->seed(TenantSeeder::class);

        $user = User::factory()->create([
            'email' => 'adminnfe@local.test',
            'password' => bcrypt('adminnfe'),
        ]);

        $client = app(ClientRepository::class)->createPasswordGrantClient(
            userId: null,
            name: 'Test Password Client',
            redirect: 'http://localhost',
            provider: 'users'
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
}