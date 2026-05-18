@extends('layouts.admin')

@section('title', 'Trocar senha')
@section('page-title', 'Trocar senha')

@section('content')
    <x-admin.page-title
        title="Trocar senha"
        subtitle="{{ $forced ? 'A troca da senha é obrigatória para continuar.' : 'Atualize sua senha de acesso administrativo.' }}"
    />

    <div class="row justify-content-center">
        <div class="col-lg-6">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.password.update') }}" data-turbo="false">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label" for="current_password">Senha atual</label>
                            <input class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" type="password" autocomplete="current-password" required autofocus>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="new_password">Nova senha</label>
                            <input class="form-control @error('new_password') is-invalid @enderror" id="new_password" name="new_password" type="password" autocomplete="new-password" required>
                            @error('new_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="new_password_confirmation">Confirmar nova senha</label>
                            <input class="form-control" id="new_password_confirmation" name="new_password_confirmation" type="password" autocomplete="new-password" required>
                        </div>

                        <button class="btn btn-primary" type="submit">
                            <i class="ri-lock-password-line align-middle me-1"></i>
                            Alterar senha
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
