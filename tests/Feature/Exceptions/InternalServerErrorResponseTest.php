<?php

declare(strict_types=1);

namespace Tests\Feature\Exceptions;

use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

final class InternalServerErrorResponseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['api'])
            ->prefix('api/v1/test/exceptions')
            ->get('/internal-error', function (): never {
                throw new RuntimeException('detalhe interno sensivel');
            });
    }

    public function test_internal_exception_returns_standard_internal_server_error_response(): void
    {
        $this->getJson('/api/v1/test/exceptions/internal-error')
            ->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Erro ao processar a requisição.',
                'errors' => [],
            ]);
    }

    public function test_internal_exception_does_not_expose_internal_details(): void
    {
        $this->getJson('/api/v1/test/exceptions/internal-error')
            ->assertStatus(500)
            ->assertDontSee('detalhe interno sensivel');
    }
}
