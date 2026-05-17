@extends('layouts.admin')

@section('title', 'Política de Senhas')
@section('page-title', 'Configurações do Sistema')

@section('content')
    <x-admin.page-title
        title="Política de Senhas"
        subtitle="Regras de senha, histórico e bloqueio isoladas por tenant."
        aside="Segurança"
    />

    @include('admin.system-settings._alerts')
    @include('admin.system-settings._tenant-selector', [
        'tenants' => $tenants,
        'selectedTenant' => $selectedTenant,
        'routeName' => 'admin.system-settings.password-policy.edit',
    ])

    @if ($selectedTenant && $policy)
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.system-settings.password-policy.update') }}" class="row g-3">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="tenant" value="{{ $selectedTenant->code }}">

                    <div class="col-md-3">
                        <label class="form-label" for="min_length">Tamanho mínimo</label>
                        <input class="form-control" id="min_length" name="min_length" type="number" min="6" max="255" value="{{ old('min_length', $policy->min_length) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="max_length">Tamanho máximo</label>
                        <input class="form-control" id="max_length" name="max_length" type="number" min="6" max="255" value="{{ old('max_length', $policy->max_length) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="password_history_count">Histórico</label>
                        <input class="form-control" id="password_history_count" name="password_history_count" type="number" min="0" max="50" value="{{ old('password_history_count', $policy->password_history_count) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="password_expiration_days">Expiração em dias</label>
                        <input class="form-control" id="password_expiration_days" name="password_expiration_days" type="number" min="1" max="3650" value="{{ old('password_expiration_days', $policy->password_expiration_days) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="max_failed_attempts">Tentativas de login</label>
                        <input class="form-control" id="max_failed_attempts" name="max_failed_attempts" type="number" min="1" max="50" value="{{ old('max_failed_attempts', $policy->max_failed_attempts) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="lockout_minutes">Bloqueio em minutos</label>
                        <input class="form-control" id="lockout_minutes" name="lockout_minutes" type="number" min="1" max="1440" value="{{ old('lockout_minutes', $policy->lockout_minutes) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="temporary_password_expiration_minutes">Senha temporária</label>
                        <input class="form-control" id="temporary_password_expiration_minutes" name="temporary_password_expiration_minutes" type="number" min="5" max="10080" value="{{ old('temporary_password_expiration_minutes', $policy->temporary_password_expiration_minutes) }}" required>
                    </div>

                    <div class="col-12">
                        <div class="row g-2">
                            @foreach ([
                                'require_uppercase' => 'Exigir maiúscula',
                                'require_lowercase' => 'Exigir minúscula',
                                'require_numbers' => 'Exigir número',
                                'require_symbols' => 'Exigir símbolo',
                                'disallow_common_passwords' => 'Bloquear senhas comuns',
                                'disallow_user_personal_data' => 'Bloquear dados pessoais',
                                'must_change_password_on_first_login' => 'Troca obrigatória no primeiro login',
                                'active' => 'Política ativa',
                            ] as $field => $label)
                                <div class="col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="{{ $field }}" name="{{ $field }}" value="1" @checked(old($field, $policy->{$field}))>
                                        <label class="form-check-label" for="{{ $field }}">{{ $label }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a class="btn btn-light" href="{{ route('admin.system-settings.index', ['tenant' => $selectedTenant->code]) }}">Voltar</a>
                        <button class="btn btn-primary" type="submit">Salvar política</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
