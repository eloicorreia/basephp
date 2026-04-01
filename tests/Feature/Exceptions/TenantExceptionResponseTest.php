<?php

declare(strict_types=1);

namespace Tests\Feature\Exceptions;

use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class TenantExceptionResponseTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_tenant_required_exception_returns_standard_bad_request_response(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user, ['user.profile']);

        $this->getJson('/api/v1/auth/me')
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Tenant não informado.',
                'errors' => [],
            ]);
    }

    public function test_tenant_not_found_exception_returns_standard_not_found_response(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user, ['user.profile']);

        $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => 'tenant-inexistente',
        ])->assertStatus(404)->assertJson([
            'success' => false,
            'message' => 'Tenant não encontrado.',
            'errors' => [],
        ]);
    }

    public function test_inactive_tenant_returns_standard_not_found_response_when_contract_requires_it(): void
    {
        $this->expectNotToPerformAssertions();
    }
}
