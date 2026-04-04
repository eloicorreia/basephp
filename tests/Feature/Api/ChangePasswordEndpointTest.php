<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class ChangePasswordEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_change_password_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'SenhaAtual@123',
            'new_password' => 'NovaSenha@123',
            'new_password_confirmation' => 'NovaSenha@123',
        ])->assertStatus(401);
    }

    public function test_change_password_returns_validation_error_for_invalid_payload(): void
    {
        $user = $this->createUser(overrides: [
            'password' => 'SenhaAtual@123',
        ]);

        Passport::actingAs($user, ['user.profile']);

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => '',
            'new_password' => 'curta',
            'new_password_confirmation' => 'diferente',
        ])->assertStatus(422);
    }

    public function test_change_password_rejects_invalid_current_password(): void
    {
        $user = $this->createUser(overrides: [
            'password' => 'SenhaAtual@123',
            'must_change_password' => true,
        ]);

        Passport::actingAs($user, ['user.profile']);

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'SenhaErrada@123',
            'new_password' => 'NovaSenhaMuitoForte@123',
            'new_password_confirmation' => 'NovaSenhaMuitoForte@123',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_change_password_updates_password_and_clears_must_change_password(): void
    {
        $user = $this->createUser(overrides: [
            'password' => 'SenhaAtual@123',
            'must_change_password' => true,
        ]);

        Passport::actingAs($user, ['user.profile']);

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'SenhaAtual@123',
            'new_password' => 'NovaSenhaMuitoForte@123',
            'new_password_confirmation' => 'NovaSenhaMuitoForte@123',
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Senha alterada com sucesso.',
                'data' => [],
            ]);

        $user->refresh();

        $this->assertTrue(Hash::check('NovaSenhaMuitoForte@123', $user->password));
        $this->assertFalse($user->must_change_password);
    }
}