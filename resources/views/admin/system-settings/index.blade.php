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
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <h5 class="card-title mb-1">Senhas</h5>
                                <p class="text-muted mb-0">Política flexível de senha e bloqueio.</p>
                            </div>
                            <a class="btn btn-primary" href="{{ route('admin.system-settings.password-policy.edit', ['tenant' => $selectedTenant->code]) }}">Configurar</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <h5 class="card-title mb-1">E-mail</h5>
                                <p class="text-muted mb-0">SMTP e remetente padrão do tenant.</p>
                            </div>
                            <a class="btn btn-primary" href="{{ route('admin.system-settings.mail.edit', ['tenant' => $selectedTenant->code]) }}">Configurar</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-warning">Nenhum tenant ativo disponível para o usuário atual.</div>
    @endif
@endsection
