@extends('layouts.admin')

@section('title', 'Editar usuário')

@section('content')
    <x-admin.page-title title="Editar usuário" subtitle="{{ $managedUser->email }}" aside="Segurança" />
    @include('admin.security._alerts')

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.security.users.update', $managedUser) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-md-6">
                    <label class="form-label" for="name">Nome</label>
                    <input class="form-control" id="name" name="name" value="{{ old('name', $managedUser->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-mail</label>
                    <input class="form-control" value="{{ $managedUser->email }}" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="role_id">Role</label>
                    <select class="form-select" id="role_id" name="role_id" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected((int) old('role_id', $managedUser->role_id) === (int) $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-check form-switch mt-4">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $managedUser->is_active))>
                    <label class="form-check-label" for="is_active">Ativo</label>
                </div>
                <div class="col-md-3 form-check form-switch mt-4">
                    <input class="form-check-input" type="checkbox" id="must_change_password" name="must_change_password" value="1" @checked(old('must_change_password', $managedUser->must_change_password))>
                    <label class="form-check-label" for="must_change_password">Trocar senha</label>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <a class="btn btn-light" href="{{ route('admin.security.users.index') }}">Voltar</a>
                    <button class="btn btn-primary" type="submit">Salvar usuário</button>
                </div>
            </form>
        </div>
    </div>
@endsection
