<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiRequestLog;
use App\Models\SystemLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\View\View;

final class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'metrics' => [
                [
                    'label' => 'Usuários',
                    'value' => User::query()->count(),
                    'icon' => 'ri-user-3-line',
                    'tone' => 'primary',
                    'meta' => 'Base pública',
                ],
                [
                    'label' => 'Tenants',
                    'value' => Tenant::query()->count(),
                    'icon' => 'ri-building-4-line',
                    'tone' => 'success',
                    'meta' => 'Ambientes cadastrados',
                ],
                [
                    'label' => 'Logs da API',
                    'value' => ApiRequestLog::query()->count(),
                    'icon' => 'ri-file-list-3-line',
                    'tone' => 'info',
                    'meta' => 'Registros operacionais',
                ],
                [
                    'label' => 'Logs de sistema',
                    'value' => SystemLog::query()->count(),
                    'icon' => 'ri-shield-check-line',
                    'tone' => 'warning',
                    'meta' => 'Eventos internos',
                ],
            ],
            'quickActions' => [
                [
                    'title' => 'Logs da API',
                    'description' => 'Consulta administrativa paginada, filtrada por período e com payload sensível protegido.',
                    'href' => route('admin.logs.api-requests.index'),
                    'icon' => 'ri-file-search-line',
                    'button' => 'Abrir logs',
                ],
                [
                    'title' => 'Base administrativa',
                    'description' => 'Estrutura inicial para módulos internos com navegação, métricas e ações de rotina.',
                    'href' => route('admin.dashboard'),
                    'icon' => 'ri-layout-3-line',
                    'button' => 'Abrir base',
                ],
            ],
        ]);
    }
}
