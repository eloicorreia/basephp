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

                    <div class="p-2 mt-4">
                        <form method="POST" action="{{ route('admin.login.store') }}">
                            @csrf

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
                                    value="{{ old('email') }}"
                                    autocomplete="email"
                                    required
                                    autofocus
                                >
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="password">Senha</label>
                                <input
                                    type="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    id="password"
                                    name="password"
                                    autocomplete="current-password"
                                    required
                                >
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Manter conectado</label>
                            </div>

                            <div class="mt-4">
                                <button class="btn btn-success w-100" type="submit">Entrar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
