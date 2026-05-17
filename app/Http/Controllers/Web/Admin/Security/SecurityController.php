<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin\Security;

use App\Http\Controllers\Controller;
use App\Services\Admin\Web\Security\SecurityOverviewService;
use Illuminate\Contracts\View\View;

final class SecurityController extends Controller
{
    public function __invoke(SecurityOverviewService $securityOverviewService): View
    {
        return view('admin.security.index', $securityOverviewService->overview());
    }
}
