@extends('layouts.admin')

@section('title', 'Usuários')

@section('content')
    <x-admin.page-title title="Usuários" subtitle="Cadastro global de acesso administrativo." aside="Segurança" />
    @include('admin.security._alerts')

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.security.users.index') }}" class="row g-3 align-items-end mb-4">
                <div class="col-md-4">
                    <label class="form-label" for="search">Busca</label>
                    <input class="form-control" id="search" name="search" value="{{ $filters['search'] }}" placeholder="Nome ou e-mail">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="role_id">Role</label>
                    <select class="form-select" id="role_id" name="role_id">
                        <option value="">Todas</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected((string) $role->id === $filters['role_id'])>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Todos</option>
                        <option value="active" @selected($filters['status'] === 'active')>Ativos</option>
                        <option value="inactive" @selected($filters['status'] === 'inactive')>Inativos</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label" for="per_page">Itens</label>
                    <select class="form-select" id="per_page" name="per_page">
                        @foreach ([20, 50, 100] as $perPage)
                            <option value="{{ $perPage }}" @selected((int) $filters['per_page'] === $perPage)>{{ $perPage }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary flex-fill" type="submit">Filtrar</button>
                    <a class="btn btn-success" href="{{ route('admin.security.users.create') }}"><i class="ri-add-line"></i></a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Senha</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $managedUser)
                            <tr>
                                <td>{{ $managedUser->name }}</td>
                                <td>{{ $managedUser->email }}</td>
                                <td>{{ $managedUser->role?->name ?? '-' }}</td>
                                <td><span class="badge {{ $managedUser->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ $managedUser->is_active ? 'Ativo' : 'Inativo' }}</span></td>
                                <td>{{ $managedUser->must_change_password ? 'Troca obrigatória' : 'Atualizada' }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.security.users.edit', $managedUser) }}">Editar</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Nenhum usuário encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $users->links() }}
        </div>
    </div>
@endsection
