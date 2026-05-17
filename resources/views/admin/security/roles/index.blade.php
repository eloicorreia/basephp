@extends('layouts.admin')

@section('title', 'Roles')

@section('content')
    <x-admin.page-title title="Roles" subtitle="Perfis globais e quantidade de permissões vinculadas." aside="Segurança" />
    @include('admin.security._alerts')

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <form method="GET" action="{{ route('admin.security.roles.index') }}" class="d-flex gap-2">
                    <select class="form-select" name="include_inactive">
                        <option value="1" @selected($filters['include_inactive'])>Todas</option>
                        <option value="0" @selected(! $filters['include_inactive'])>Somente ativas</option>
                    </select>
                    <button class="btn btn-primary" type="submit">Filtrar</button>
                </form>
                <a class="btn btn-success" href="{{ route('admin.security.roles.create') }}"><i class="ri-add-line me-1"></i>Nova role</a>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Role</th>
                            <th>Código</th>
                            <th>Usuários</th>
                            <th>Permissões</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <td>{{ $role->name }}</td>
                                <td><code>{{ $role->code }}</code></td>
                                <td>{{ $role->users_count }}</td>
                                <td>{{ $role->permissions_count }}</td>
                                <td><span class="badge {{ $role->active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ $role->active ? 'Ativa' : 'Inativa' }}</span></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.security.roles.edit', $role) }}">Editar</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma role encontrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $roles->links() }}
        </div>
    </div>
@endsection
