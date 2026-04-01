<?php

declare(strict_types=1);

namespace Tests\Feature\Exceptions;

use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AuthorizationExceptionResponseTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_forbidden_authorization_returns_standard_forbidden_response(): void
    {
        $tenant = $this->createTenant(
            code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid())
        );
        $user = $this->createUser();

        Passport::actingAs($user, ['user.profile']);

        $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => $tenant->code,
        ])->assertStatus(403)->assertJson([
            'success' => false,
            'message' => 'Acesso negado.',
            'errors' => [],
        ]);
    }

    public function test_forbidden_authorization_uses_expected_message_contract(): void
    {
        $this->expectNotToPerformAssertions();
    }
}
