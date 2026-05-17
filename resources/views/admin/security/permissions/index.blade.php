@extends('layouts.admin')

@section('title', 'Permissões')

@section('content')
    <x-admin.page-title title="Permissões" subtitle="Contratos de autorização usados por roles, APIs e painel web." aside="Segurança" />
    @include('admin.security._alerts')

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.security.permissions.index') }}" class="row g-3 align-items-end mb-4">
                <div class="col-md-3">
                    <label class="form-label" for="context">Contexto</label>
                    <select class="form-select" id="context" name="context">
                        <option value="">Todos</option>
                        @foreach (['api', 'web', 'both'] as $context)
                            <option value="{{ $context }}" @selected($filters['context'] === $context)>{{ $context }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-check form-switch mt-4">
                    <input class="form-check-input" type="checkbox" id="active_only" name="active_only" value="1" @checked($filters['active_only'])>
                    <label class="form-check-label" for="active_only">Somente ativas</label>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="per_page">Itens</label>
                    <select class="form-select" id="per_page" name="per_page">
                        @foreach ([50, 100] as $perPage)
                            <option value="{{ $perPage }}" @selected((int) $filters['per_page'] === $perPage)>{{ $perPage }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Filtrar</button>
                    <a class="btn btn-success ms-auto" href="{{ route('admin.security.permissions.create') }}"><i class="ri-add-line me-1"></i>Nova permissão</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Nome</th>
                            <th>Grupo</th>
                            <th>Contexto</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($permissions as $permission)
                            <tr>
                                <td><code>{{ $permission->code }}</code></td>
                                <td>{{ $permission->name }}</td>
                                <td>{{ $permission->group ?? '-' }}</td>
                                <td><span class="badge bg-info-subtle text-info">{{ $permission->context }}</span></td>
                                <td>{{ $permission->is_system ? 'Sistema' : 'Customizada' }}</td>
                                <td><span class="badge {{ $permission->active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ $permission->active ? 'Ativa' : 'Inativa' }}</span></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.security.permissions.edit', $permission) }}">Editar</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Nenhuma permissão encontrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $permissions->links() }}
        </div>
    </div>
@endsection
