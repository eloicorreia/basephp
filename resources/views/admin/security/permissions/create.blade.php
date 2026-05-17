@extends('layouts.admin')

@section('title', 'Nova permissão')

@section('content')
    <x-admin.page-title title="Nova permissão" subtitle="Crie permissões customizadas sem apagar contratos existentes." aside="Segurança" />
    @include('admin.security._alerts')

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.security.permissions.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="code">Código</label>
                    <input class="form-control" id="code" name="code" value="{{ old('code') }}" placeholder="admin.module.action" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="name">Nome</label>
                    <input class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="group">Grupo</label>
                    <input class="form-control" id="group" name="group" value="{{ old('group') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="context">Contexto</label>
                    <select class="form-select" id="context" name="context" required>
                        @foreach (['api', 'web', 'both'] as $context)
                            <option value="{{ $context }}" @selected(old('context', 'web') === $context)>{{ $context }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="description">Descrição</label>
                    <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                </div>
                <div class="col-12 form-check form-switch ms-2">
                    <input class="form-check-input" type="checkbox" id="is_sensitive" name="is_sensitive" value="1" @checked(old('is_sensitive'))>
                    <label class="form-check-label" for="is_sensitive">Permissão sensível</label>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <a class="btn btn-light" href="{{ route('admin.security.permissions.index') }}">Cancelar</a>
                    <button class="btn btn-primary" type="submit">Criar permissão</button>
                </div>
            </form>
        </div>
    </div>
@endsection
