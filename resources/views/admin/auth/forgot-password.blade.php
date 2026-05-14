@extends('layouts.admin-auth')

@section('title', 'Recuperar senha')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
            <div class="card mt-4 card-bg-fill">
                <div class="card-body p-4">
                    <div class="text-center mt-2">
                        <h5 class="text-primary">Recuperar senha</h5>
                        <p class="text-muted">Informe o e-mail da conta administrativa.</p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success text-center mt-4 mb-0">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="p-2 mt-4">
                        <form method="POST" action="{{ route('password.email') }}">
                            @csrf

                            <div class="mb-4">
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
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="text-center mt-4">
                                <button class="btn btn-success w-100" type="submit">
                                    <i class="ri-mail-send-line align-middle me-1"></i>
                                    Enviar link
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
