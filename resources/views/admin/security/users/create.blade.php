@extends('layouts.admin')

@section('title', 'Novo usuário')

@section('content')
    <x-admin.page-title title="Novo usuário" subtitle="Crie um acesso global com role e senha temporária." aside="Segurança" />
    @include('admin.security._alerts')

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.security.users.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="name">Nome</label>
                    <input class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">E-mail</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="role_id">Role</label>
                    <select class="form-select" id="role_id" name="role_id" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="password">Senha temporária</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="password_confirmation">Confirmação</label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                </div>
                <div class="col-md-6 form-check form-switch ms-2">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', true))>
                    <label class="form-check-label" for="is_active">Usuário ativo</label>
                </div>
                <div class="col-md-6 form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="must_change_password" name="must_change_password" value="1" @checked(old('must_change_password', true))>
                    <label class="form-check-label" for="must_change_password">Exigir troca de senha</label>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <a class="btn btn-light" href="{{ route('admin.security.users.index') }}">Cancelar</a>
                    <button class="btn btn-primary" type="submit">Criar usuário</button>
                </div>
            </form>
        </div>
    </div>
@endsection
