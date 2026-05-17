@extends('layouts.admin')

@section('title', 'Configurações do Sistema')
@section('page-title', 'Configurações do Sistema')

@section('content')
    <x-admin.page-title
        title="Configurações do Sistema"
        subtitle="Configurações operacionais isoladas por tenant."
        aside="Tenant"
    />

    @include('admin.system-settings._alerts')
    @include('admin.system-settings._tenant-selector', [
        'tenants' => $tenants,
        'selectedTenant' => $selectedTenant,
        'routeName' => 'admin.system-settings.index',
    ])

    @if ($selectedTenant)
        <div class="row">
            @foreach ([
                ['title' => 'Geral', 'meta' => 'Localização, paginação e manutenção.', 'route' => 'admin.system-settings.general.edit'],
                ['title' => 'Segurança', 'meta' => 'Sessão, bloqueio e políticas de login.', 'route' => 'admin.system-settings.security.edit'],
                ['title' => 'Senhas', 'meta' => 'Política flexível de senha e bloqueio.', 'route' => 'admin.system-settings.password-policy.edit'],
                ['title' => 'E-mail', 'meta' => 'SMTP e remetente padrão do tenant.', 'route' => 'admin.system-settings.mail.edit'],
                ['title' => 'API', 'meta' => 'Rate limit, paginação e origens.', 'route' => 'admin.system-settings.api.edit'],
                ['title' => 'Filas', 'meta' => 'Queues, tentativas e processamento.', 'route' => 'admin.system-settings.queues.edit'],
                ['title' => 'Logs e Auditoria', 'meta' => 'Auditoria e retenção de logs.', 'route' => 'admin.system-settings.audit.edit'],
                ['title' => 'Integrações', 'meta' => 'Timeout, retentativas e circuit breaker.', 'route' => 'admin.system-settings.integrations.edit'],
                ['title' => 'Webhooks', 'meta' => 'Endpoint, segredo e eventos.', 'route' => 'admin.system-settings.webhooks.edit'],
                ['title' => 'Notificações', 'meta' => 'Alertas administrativos.', 'route' => 'admin.system-settings.notifications.edit'],
            ] as $item)
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <h5 class="card-title mb-1">{{ $item['title'] }}</h5>
                                <p class="text-muted mb-0">{{ $item['meta'] }}</p>
                            </div>
                            <a class="btn btn-primary" href="{{ route($item['route'], ['tenant' => $selectedTenant->code]) }}">Configurar</a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-warning">Nenhum tenant ativo disponível para o usuário atual.</div>
    @endif
@endsection
