@extends('layouts.admin')

@section('title', 'Editar role')

@section('content')
    <x-admin.page-title title="Editar role" subtitle="{{ $managedRole->code }}" aside="Segurança" />
    @include('admin.security._alerts')

    <form method="POST" action="{{ route('admin.security.roles.update', $managedRole) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="name">Nome</label>
                            <input class="form-control" id="name" name="name" value="{{ old('name', $managedRole->name) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Código</label>
                            <input class="form-control" value="{{ $managedRole->code }}" disabled>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="active" name="active" value="1" @checked(old('active', $managedRole->active))>
                            <label class="form-check-label" for="active">Role ativa</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Permissões</h5>
                        @foreach ($permissionsByGroup as $group => $permissions)
                            <div class="mb-4">
                                <div class="fw-semibold text-muted text-uppercase small mb-2">{{ $group ?: 'Sem grupo' }}</div>
                                <div class="row g-2">
                                    @foreach ($permissions as $permission)
                                        <div class="col-md-6">
                                            <div class="form-check border rounded p-2 ps-4 h-100">
                                                <input class="form-check-input" type="checkbox" id="permission_{{ $permission->id }}" name="permission_ids[]" value="{{ $permission->id }}" @checked(in_array($permission->id, old('permission_ids', $managedRole->permissions->pluck('id')->all()), true))>
                                                <label class="form-check-label w-100" for="permission_{{ $permission->id }}">
                                                    <span class="d-block fw-semibold">{{ $permission->name }}</span>
                                                    <small class="text-muted">{{ $permission->code }}</small>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mb-4">
            <a class="btn btn-light" href="{{ route('admin.security.roles.index') }}">Voltar</a>
            <button class="btn btn-primary" type="submit">Salvar role</button>
        </div>
    </form>
@endsection
