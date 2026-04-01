<?php

declare(strict_types=1);

namespace Tests\Feature\Exceptions;

use Tests\TestCase;

final class ValidationExceptionResponseTest extends TestCase
{
    public function test_validation_exception_returns_standard_validation_response(): void
    {
        $this->markTestIncomplete(
            'Cobrir via endpoint real ou rota de teste dedicada.'
        );
    }

    public function test_validation_exception_includes_expected_errors_structure(): void
    {
        $this->markTestIncomplete(
            'Cobrir junto ao cenário de validation error.'
        );
    }
}
