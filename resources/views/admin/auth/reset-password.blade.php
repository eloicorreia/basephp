@extends('layouts.admin-auth')

@section('title', 'Redefinir senha')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
            <div class="card mt-4 card-bg-fill">
                <div class="card-body p-4">
                    <div class="text-center mt-2">
                        <h5 class="text-primary">Redefinir senha</h5>
                        <p class="text-muted">Defina uma nova senha administrativa.</p>
                    </div>

                    <div class="p-2 mt-4">
                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input
                                    type="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    id="email"
                                    name="email"
                                    value="{{ old('email', is_string($email) ? $email : '') }}"
                                    autocomplete="email"
                                    required
                                    autofocus
                                >
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="password">Nova senha</label>
                                <div class="position-relative auth-pass-inputgroup mb-3">
                                    <input
                                        type="password"
                                        class="form-control pe-5 password-input @error('password') is-invalid @enderror"
                                        id="password"
                                        name="password"
                                        autocomplete="new-password"
                                        required
                                    >
                                    <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button">
                                        <i class="ri-eye-fill align-middle"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="password_confirmation">Confirmar senha</label>
                                <div class="position-relative auth-pass-inputgroup mb-3">
                                    <input
                                        type="password"
                                        class="form-control pe-5 password-input"
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        autocomplete="new-password"
                                        required
                                    >
                                    <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button">
                                        <i class="ri-eye-fill align-middle"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button class="btn btn-success w-100" type="submit">
                                    <i class="ri-shield-keyhole-line align-middle me-1"></i>
                                    Salvar senha
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-center">
                <p class="mb-0">
                    <a href="{{ route('login') }}" class="fw-semibold text-primary text-decoration-underline">Voltar ao login</a>
                </p>
            </div>
        </div>
    </div>
@endsection
