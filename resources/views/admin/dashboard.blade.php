@extends('layouts.admin')

@section('title', 'Dashboard administrativo')
@section('page-title', 'Dashboard administrativo')

@section('content')
    <x-admin.page-title
        title="Visão operacional"
        subtitle="Indicadores e atalhos do módulo web."
        aside="Sessão web administrativa"
    />

    <div class="row admin-dashboard-shell">
        @foreach ($metrics as $metric)
            <div class="col-xl-3 col-md-6">
                <x-admin.metric-card
                    :label="$metric['label']"
                    :value="number_format((int) $metric['value'], 0, ',', '.')"
                    :icon="$metric['icon']"
                    :tone="$metric['tone']"
                    :meta="$metric['meta']"
                />
            </div>
        @endforeach
    </div>

    <div class="row">
        @foreach ($quickActions as $action)
            <div class="col-lg-6">
                <x-admin.action-card
                    :title="$action['title']"
                    :description="$action['description']"
                    :href="$action['href']"
                    :icon="$action['icon']"
                    :button="$action['button']"
                />
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card admin-template-preview">
                <div class="card-body">
                    <h5 class="card-title mb-2">Mapa administrativo</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="d-flex gap-3">
                                <span class="admin-stat-icon bg-success-subtle text-success"><i class="ri-building-4-line fs-20"></i></span>
                                <div>
                                    <h6 class="mb-1">Tenants</h6>
                                    <p class="text-muted mb-0">Status, schemas e provisionamento.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex gap-3">
                                <span class="admin-stat-icon bg-info-subtle text-info"><i class="ri-team-line fs-20"></i></span>
                                <div>
                                    <h6 class="mb-1">Usuários</h6>
                                    <p class="text-muted mb-0">Acessos, papéis e vínculos.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex gap-3">
                                <span class="admin-stat-icon bg-warning-subtle text-warning"><i class="ri-settings-3-line fs-20"></i></span>
                                <div>
                                    <h6 class="mb-1">Configurações</h6>
                                    <p class="text-muted mb-0">Parâmetros operacionais protegidos.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
