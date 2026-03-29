<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Laravel\Passport\Passport;
use Tests\TestCase;

class TenantResolutionTest extends TestCase
{
    public function test_it_requires_tenant_header(): void
    {
        $user = \App\Models\User::factory()->create();

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Tenant não informado.',
                'errors' => [],
            ]);
    }
}