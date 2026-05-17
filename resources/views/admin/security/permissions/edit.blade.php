@extends('layouts.admin')

@section('title', 'Editar permissão')

@section('content')
    <x-admin.page-title title="Editar permissão" subtitle="{{ $permission->code }}" aside="Segurança" />
    @include('admin.security._alerts')

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.security.permissions.update', $permission) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-md-6">
                    <label class="form-label">Código</label>
                    <input class="form-control" value="{{ $permission->code }}" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="name">Nome</label>
                    <input class="form-control" id="name" name="name" value="{{ old('name', $permission->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="group">Grupo</label>
                    <input class="form-control" id="group" name="group" value="{{ old('group', $permission->group) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="context">Contexto</label>
                    <select class="form-select" id="context" name="context" required @disabled($permission->is_system)>
                        @foreach (['api', 'web', 'both'] as $context)
                            <option value="{{ $context }}" @selected(old('context', $permission->context) === $context)>{{ $context }}</option>
                        @endforeach
                    </select>
                    @if ($permission->is_system)
                        <input type="hidden" name="context" value="{{ $permission->context }}">
                    @endif
                </div>
                <div class="col-12">
                    <label class="form-label" for="description">Descrição</label>
                    <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $permission->description) }}</textarea>
                </div>
                <div class="col-12 form-check form-switch ms-2">
                    <input class="form-check-input" type="checkbox" id="is_sensitive" name="is_sensitive" value="1" @checked(old('is_sensitive', $permission->is_sensitive)) @disabled($permission->is_system)>
                    <label class="form-check-label" for="is_sensitive">Permissão sensível</label>
                    @if ($permission->is_system)
                        <input type="hidden" name="is_sensitive" value="{{ $permission->is_sensitive ? 1 : 0 }}">
                    @endif
                </div>
                <div class="col-12 d-flex justify-content-between gap-2">
                    <div>
                        @if ($permission->active)
                            <button class="btn btn-outline-danger" type="submit" form="disable-permission" @disabled($permission->is_system)>Desabilitar</button>
                        @else
                            <button class="btn btn-outline-success" type="submit" form="enable-permission">Habilitar</button>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-light" href="{{ route('admin.security.permissions.index') }}">Voltar</a>
                        <button class="btn btn-primary" type="submit">Salvar permissão</button>
                    </div>
                </div>
            </form>

            <form id="disable-permission" method="POST" action="{{ route('admin.security.permissions.disable', $permission) }}">
                @csrf
                @method('PATCH')
            </form>
            <form id="enable-permission" method="POST" action="{{ route('admin.security.permissions.enable', $permission) }}">
                @csrf
                @method('PATCH')
            </form>
        </div>
    </div>
@endsection
