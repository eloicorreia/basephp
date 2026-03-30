<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\LoadsProjectMigrations;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use LoadsProjectMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadProjectMigrations();
    }
}