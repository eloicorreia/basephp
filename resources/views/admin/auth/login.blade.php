@extends('layouts.admin-auth')

@section('title', 'Login administrativo')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
            <div class="card mt-4 card-bg-fill">
                <div class="card-body p-4">
                    <div class="text-center mt-2">
                        <h5 class="text-primary">Acesso administrativo</h5>
                        <p class="text-muted">Entre com sua conta administrativa.</p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success text-center mt-4 mb-0">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="p-2 mt-4">
                        <form method="POST" action="{{ route('admin.login.store') }}">
                            @csrf

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <div class="mb-3">
                                <label for="tenant_code" class="form-label">Tenant</label>
                                <input
                                    type="text"
                                    class="form-control @error('tenant_code') is-invalid @enderror"
                                    id="tenant_code"
                                    name="tenant_code"
                                    value="{{ old('tenant_code') }}"
                                    autocomplete="organization"
                                    placeholder="codigo-do-tenant"
                                    required
                                    autofocus
                                >
                                @error('tenant_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input
                                    type="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    autocomplete="email"
                                    required
                                >
                            </div>

                            <div class="mb-3">
                                <div class="float-end">
                                    <a href="{{ route('password.request') }}" tabindex="-1" class="text-muted">Esqueci minha senha</a>
                                </div>
                                <label class="form-label" for="password">Senha</label>
                                <div class="position-relative auth-pass-inputgroup mb-3">
                                    <input
                                        type="password"
                                        class="form-control pe-5 password-input @error('password') is-invalid @enderror"
                                        id="password"
                                        name="password"
                                        autocomplete="current-password"
                                        required
                                    >
                                    <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" tabindex="-1" type="button">
                                        <i class="ri-eye-fill align-middle"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button class="btn btn-success w-100" type="submit">
                                    <i class="ri-login-box-line align-middle me-1"></i>
                                    Entrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
