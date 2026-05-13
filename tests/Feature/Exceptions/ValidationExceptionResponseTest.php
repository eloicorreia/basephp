<?php

declare(strict_types=1);

namespace Tests\Feature\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ValidationExceptionResponseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['api'])
            ->prefix('api/v1/test/exceptions')
            ->post('/validation-error', function (Request $request): JsonResponse {
                $validated = validator($request->all(), [
                    'name' => ['required', 'string', 'min:3'],
                ])->validate();

                return response()->json([
                    'success' => true,
                    'data' => $validated,
                ]);
            });
    }

    public function test_validation_exception_returns_standard_validation_response(): void
    {
        $this->postJson('/api/v1/test/exceptions/validation-error', [
            'name' => 'ab',
        ])->assertStatus(422)->assertJson([
            'success' => false,
            'message' => 'Erro de validação.',
        ]);
    }

    public function test_validation_exception_includes_expected_errors_structure(): void
    {
        $this->postJson('/api/v1/test/exceptions/validation-error', [
            'name' => 'ab',
        ])->assertStatus(422)
            ->assertJsonPath('errors.0.field', 'name')
            ->assertJsonPath('errors.0.type', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => [
                    [
                        'field',
                        'message',
                        'type',
                    ],
                ],
            ]);
    }
}
