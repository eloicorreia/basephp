@extends('layouts.admin')

@section('title', 'Segurança')

@section('content')
    <x-admin.page-title
        title="Segurança"
        subtitle="Árvore de usuários, roles e permissões do painel administrativo."
        aside="Governança de acesso"
    />

    @include('admin.security._alerts')

    <div class="row">
        @foreach ([
            ['label' => 'Usuários', 'value' => $metrics['users'], 'icon' => 'ri-user-3-line', 'tone' => 'primary', 'meta' => $metrics['active_users'].' ativos'],
            ['label' => 'Roles', 'value' => $metrics['roles'], 'icon' => 'ri-shield-user-line', 'tone' => 'success', 'meta' => 'Perfis globais'],
            ['label' => 'Permissões', 'value' => $metrics['permissions'], 'icon' => 'ri-key-2-line', 'tone' => 'warning', 'meta' => 'Contratos de autorização'],
        ] as $metric)
            <div class="col-xl-4 col-md-6">
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
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Cadastros</h5>
                    <div class="list-group">
                        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="{{ route('admin.security.users.index') }}">
                            <span><i class="ri-user-settings-line me-2"></i>Usuários</span>
                            <i class="ri-arrow-right-s-line"></i>
                        </a>
                        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="{{ route('admin.security.roles.index') }}">
                            <span><i class="ri-shield-user-line me-2"></i>Roles</span>
                            <i class="ri-arrow-right-s-line"></i>
                        </a>
                        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="{{ route('admin.security.permissions.index') }}">
                            <span><i class="ri-key-2-line me-2"></i>Permissões</span>
                            <i class="ri-arrow-right-s-line"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Árvore de segurança</h5>
                    <div class="accordion" id="securityTree">
                        @foreach ($roles as $role)
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="role-heading-{{ $role->id }}">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#role-collapse-{{ $role->id }}" aria-expanded="false" aria-controls="role-collapse-{{ $role->id }}">
                                        <span class="me-2">{{ $role->name }}</span>
                                        <span class="badge bg-primary-subtle text-primary">{{ $role->permissions_count }} permissões</span>
                                        <span class="badge bg-secondary-subtle text-secondary ms-2">{{ $role->users_count }} usuários</span>
                                    </button>
                                </h2>
                                <div id="role-collapse-{{ $role->id }}" class="accordion-collapse collapse" aria-labelledby="role-heading-{{ $role->id }}" data-bs-parent="#securityTree">
                                    <div class="accordion-body">
                                        @foreach ($role->permissions->groupBy('group') as $group => $permissions)
                                            <div class="mb-3">
                                                <div class="text-muted text-uppercase fw-semibold small mb-2">{{ $group ?: 'Sem grupo' }}</div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach ($permissions as $permission)
                                                        <span class="badge bg-light text-body border">{{ $permission->code }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
