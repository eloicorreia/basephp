<?php

declare(strict_types=1);

namespace Tests\Feature\Exceptions;

use Tests\TestCase;

final class InternalServerErrorResponseTest extends TestCase
{
    public function test_internal_exception_returns_standard_internal_server_error_response(): void
    {
        $this->markTestIncomplete(
            'Cobrir via rota de teste dedicada.'
        );
    }

    public function test_internal_exception_does_not_expose_internal_details(): void
    {
        $this->markTestIncomplete(
            'Cobrir junto ao cenário de internal server error.'
        );
    }
}
