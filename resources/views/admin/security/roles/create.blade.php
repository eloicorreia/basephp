@extends('layouts.admin')

@section('title', 'Nova role')

@section('content')
    <x-admin.page-title title="Nova role" subtitle="Crie um perfil global para vincular permissões." aside="Segurança" />
    @include('admin.security._alerts')

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.security.roles.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="name">Nome</label>
                    <input class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="code">Código</label>
                    <input class="form-control" id="code" name="code" value="{{ old('code') }}" placeholder="security.operator" required>
                </div>
                <div class="col-12 form-check form-switch ms-2">
                    <input class="form-check-input" type="checkbox" id="active" name="active" value="1" @checked(old('active', true))>
                    <label class="form-check-label" for="active">Role ativa</label>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <a class="btn btn-light" href="{{ route('admin.security.roles.index') }}">Cancelar</a>
                    <button class="btn btn-primary" type="submit">Criar role</button>
                </div>
            </form>
        </div>
    </div>
@endsection
