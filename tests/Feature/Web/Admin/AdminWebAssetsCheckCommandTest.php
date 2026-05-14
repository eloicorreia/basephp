<?php

declare(strict_types=1);

namespace Tests\Feature\Web\Admin;

use Tests\TestCase;

final class AdminWebAssetsCheckCommandTest extends TestCase
{
    public function test_web_assets_check_validates_static_template_contract(): void
    {
        $this->artisan('web:assets:check')
            ->assertSuccessful();
    }
}
