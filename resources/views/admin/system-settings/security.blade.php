@extends('layouts.admin')

@section('title', 'Segurança do Tenant')
@section('page-title', 'Configurações do Sistema')

@php
    $helpTexts = [
        'session_lifetime_minutes' => 'Tempo total de validade da sessão web do tenant. Depois desse período o usuário precisa autenticar novamente.',
        'idle_timeout_minutes' => 'Tempo máximo sem atividade antes de encerrar a sessão. Deixe vazio para não aplicar timeout por inatividade.',
        'max_login_attempts' => 'Quantidade de tentativas de login com falha permitidas antes de aplicar bloqueio temporário.',
        'lockout_duration_minutes' => 'Tempo em minutos que o usuário permanece bloqueado após exceder o limite de tentativas.',
        'force_single_session_per_user' => 'Impede sessões simultâneas do mesmo usuário, ajudando a reduzir compartilhamento de credenciais.',
        'logout_on_password_change' => 'Encerra sessões abertas quando a senha é alterada, forçando novo login com a credencial atual.',
        'unlock_requires_admin' => 'Exige ação administrativa para desbloquear usuários, mesmo após falhas de autenticação.',
        'notify_user_on_failed_login' => 'Envia aviso ao usuário quando houver tentativa de login malsucedida em sua conta.',
        'notify_admin_on_lockout' => 'Notifica administradores quando um usuário for bloqueado por falhas repetidas de login.',
        'allowed_ip_ranges' => 'Lista opcional de IPs ou CIDRs autorizados para acessar o tenant. Informe um item por linha, como 192.168.0.10 ou 10.0.0.0/24.',
    ];

    $helpIcon = static function (string $field) use ($helpTexts): string {
        return '<button type="button" class="btn btn-link p-0 ms-1 text-muted align-baseline system-setting-help" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="'.e($helpTexts[$field]).'" aria-label="Ajuda sobre '.$field.'">?</button>';
    };
@endphp

@section('content')
    <x-admin.page-title title="Segurança" subtitle="Sessão, bloqueio de login e restrições por tenant." aside="Tenant" />
    @include('admin.system-settings._alerts')
    @include('admin.system-settings._tenant-selector', ['tenants' => $tenants, 'selectedTenant' => $selectedTenant, 'routeName' => 'admin.system-settings.security.edit'])
    @include('admin.system-settings._load-state')
    @if ($selectedTenant && $setting && empty($loadError))
        <div class="card"><div class="card-body"><form method="POST" action="{{ route('admin.system-settings.security.update') }}" class="row g-3">
            @csrf @method('PUT')<input type="hidden" name="tenant" value="{{ $selectedTenant->code }}">
            <div class="col-md-3"><label class="form-label" for="session_lifetime_minutes">Sessão (min){!! $helpIcon('session_lifetime_minutes') !!}</label><input class="form-control" id="session_lifetime_minutes" name="session_lifetime_minutes" type="number" min="5" max="10080" value="{{ old('session_lifetime_minutes', $setting->session_lifetime_minutes) }}" required></div>
            <div class="col-md-3"><label class="form-label" for="idle_timeout_minutes">Inatividade (min){!! $helpIcon('idle_timeout_minutes') !!}</label><input class="form-control" id="idle_timeout_minutes" name="idle_timeout_minutes" type="number" min="1" max="10080" value="{{ old('idle_timeout_minutes', $setting->idle_timeout_minutes) }}"></div>
            <div class="col-md-3"><label class="form-label" for="max_login_attempts">Tentativas{!! $helpIcon('max_login_attempts') !!}</label><input class="form-control" id="max_login_attempts" name="max_login_attempts" type="number" min="1" max="50" value="{{ old('max_login_attempts', $setting->max_login_attempts) }}" required></div>
            <div class="col-md-3"><label class="form-label" for="lockout_duration_minutes">Bloqueio (min){!! $helpIcon('lockout_duration_minutes') !!}</label><input class="form-control" id="lockout_duration_minutes" name="lockout_duration_minutes" type="number" min="1" max="1440" value="{{ old('lockout_duration_minutes', $setting->lockout_duration_minutes) }}" required></div>
            @foreach (['force_single_session_per_user' => 'Sessão única por usuário', 'logout_on_password_change' => 'Logout ao trocar senha', 'unlock_requires_admin' => 'Desbloqueio exige admin', 'notify_user_on_failed_login' => 'Notificar usuário em falha', 'notify_admin_on_lockout' => 'Notificar admin em bloqueio'] as $field => $label)
                <div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="{{ $field }}" name="{{ $field }}" value="1" @checked(old($field, $setting->{$field}))><label class="form-check-label" for="{{ $field }}">{{ $label }}{!! $helpIcon($field) !!}</label></div></div>
            @endforeach
            <div class="col-12"><label class="form-label" for="allowed_ip_ranges">IPs/CIDRs permitidos{!! $helpIcon('allowed_ip_ranges') !!}</label><textarea class="form-control font-monospace" id="allowed_ip_ranges" name="allowed_ip_ranges" rows="4" placeholder="192.168.0.10&#10;10.0.0.0/24">{{ old('allowed_ip_ranges', implode("\n", $setting->allowed_ip_ranges ?? [])) }}</textarea></div>
            <div class="col-12 d-flex justify-content-end gap-2"><a class="btn btn-light" href="{{ route('admin.system-settings.index', ['tenant' => $selectedTenant->code]) }}">Voltar</a><button class="btn btn-primary" type="submit">Salvar</button></div>
        </form></div></div>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
                new bootstrap.Tooltip(element);
            });
        });
    </script>
@endpush
