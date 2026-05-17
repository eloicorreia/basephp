@extends('layouts.admin')

@section('title', 'Segurança do Tenant')
@section('page-title', 'Configurações do Sistema')

@section('content')
    <x-admin.page-title title="Segurança" subtitle="Sessão, bloqueio de login e restrições por tenant." aside="Tenant" />
    @include('admin.system-settings._alerts')
    @include('admin.system-settings._tenant-selector', ['tenants' => $tenants, 'selectedTenant' => $selectedTenant, 'routeName' => 'admin.system-settings.security.edit'])
    @include('admin.system-settings._load-state')
    @if ($selectedTenant && $setting && empty($loadError))
        <div class="card"><div class="card-body"><form method="POST" action="{{ route('admin.system-settings.security.update') }}" class="row g-3">
            @csrf @method('PUT')<input type="hidden" name="tenant" value="{{ $selectedTenant->code }}">
            <div class="col-md-3"><label class="form-label" for="session_lifetime_minutes">Sessão (min)</label><input class="form-control" id="session_lifetime_minutes" name="session_lifetime_minutes" type="number" value="{{ old('session_lifetime_minutes', $setting->session_lifetime_minutes) }}" required></div>
            <div class="col-md-3"><label class="form-label" for="idle_timeout_minutes">Inatividade (min)</label><input class="form-control" id="idle_timeout_minutes" name="idle_timeout_minutes" type="number" value="{{ old('idle_timeout_minutes', $setting->idle_timeout_minutes) }}"></div>
            <div class="col-md-3"><label class="form-label" for="max_login_attempts">Tentativas</label><input class="form-control" id="max_login_attempts" name="max_login_attempts" type="number" value="{{ old('max_login_attempts', $setting->max_login_attempts) }}" required></div>
            <div class="col-md-3"><label class="form-label" for="lockout_duration_minutes">Bloqueio (min)</label><input class="form-control" id="lockout_duration_minutes" name="lockout_duration_minutes" type="number" value="{{ old('lockout_duration_minutes', $setting->lockout_duration_minutes) }}" required></div>
            @foreach (['force_single_session_per_user' => 'Sessão única por usuário', 'logout_on_password_change' => 'Logout ao trocar senha', 'unlock_requires_admin' => 'Desbloqueio exige admin', 'notify_user_on_failed_login' => 'Notificar usuário em falha', 'notify_admin_on_lockout' => 'Notificar admin em bloqueio'] as $field => $label)
                <div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="{{ $field }}" name="{{ $field }}" value="1" @checked(old($field, $setting->{$field}))><label class="form-check-label" for="{{ $field }}">{{ $label }}</label></div></div>
            @endforeach
            <div class="col-12"><label class="form-label" for="allowed_ip_ranges">IPs/CIDRs permitidos</label><textarea class="form-control" id="allowed_ip_ranges" name="allowed_ip_ranges" rows="4">{{ old('allowed_ip_ranges', implode("\n", $setting->allowed_ip_ranges ?? [])) }}</textarea></div>
            <div class="col-12 d-flex justify-content-end gap-2"><a class="btn btn-light" href="{{ route('admin.system-settings.index', ['tenant' => $selectedTenant->code]) }}">Voltar</a><button class="btn btn-primary" type="submit">Salvar</button></div>
        </form></div></div>
    @endif
@endsection
