<?php

declare(strict_types=1);

namespace Tests\Feature\Documentation;

use App\Http\Middleware\EnsureDocumentationAccess;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class DocumentationAccessMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(EnsureDocumentationAccess::class)
            ->get('/documentation-access-test', fn () => response()->json(['ok' => true]));
    }

    public function test_it_allows_documentation_when_public_access_is_enabled(): void
    {
        config(['l5-swagger.defaults.access.public' => true]);

        $this->getJson('/documentation-access-test')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_it_hides_documentation_when_public_access_is_disabled(): void
    {
        config([
            'l5-swagger.defaults.access.public' => false,
            'l5-swagger.defaults.access.allowed_ips' => [],
        ]);

        $this->getJson('/documentation-access-test')
            ->assertNotFound();
    }

    public function test_it_allows_documentation_for_explicitly_allowed_ip(): void
    {
        config([
            'l5-swagger.defaults.access.public' => false,
            'l5-swagger.defaults.access.allowed_ips' => ['127.0.0.1'],
        ]);

        $this->getJson('/documentation-access-test')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }
}
