@extends('layouts.admin')

@section('title', 'Integrações')
@section('page-title', 'Configurações do Sistema')

@php
    $helpTexts = [
        'default_timeout_seconds' => 'Tempo máximo, em segundos, para chamadas de integrações externas antes de abortar a requisição.',
        'retry_attempts' => 'Quantidade de novas tentativas após uma falha temporária de integração.',
        'retry_backoff_seconds' => 'Intervalo base, em segundos, entre tentativas de integração.',
        'circuit_breaker_failure_threshold' => 'Número de falhas consecutivas que abre o circuit breaker e pausa novas chamadas temporariamente.',
        'integration_enabled' => 'Habilita ou desabilita rotinas de integração externa para este tenant.',
        'circuit_breaker_enabled' => 'Ativa proteção para interromper chamadas externas quando o serviço integrado aparenta estar indisponível.',
    ];
@endphp

@section('content')
    <x-admin.page-title title="Integrações" subtitle="Timeouts, retentativas e circuit breaker." aside="Tenant" />
    @include('admin.system-settings._alerts')
    @include('admin.system-settings._tenant-selector', ['tenants' => $tenants, 'selectedTenant' => $selectedTenant, 'routeName' => 'admin.system-settings.integrations.edit'])
    @include('admin.system-settings._load-state')
    @if ($selectedTenant && $setting && empty($loadError))
        <div class="card"><div class="card-body"><form method="POST" action="{{ route('admin.system-settings.integrations.update') }}" class="row g-3">
            @csrf @method('PUT')<input type="hidden" name="tenant" value="{{ $selectedTenant->code }}">
            <div class="col-md-3"><label class="form-label" for="default_timeout_seconds">Timeout<x-admin.help-icon :text="$helpTexts['default_timeout_seconds']" label="Ajuda sobre timeout de integrações" /></label><input class="form-control" id="default_timeout_seconds" name="default_timeout_seconds" type="number" min="1" max="300" value="{{ old('default_timeout_seconds', $setting->default_timeout_seconds) }}" required></div>
            <div class="col-md-3"><label class="form-label" for="retry_attempts">Retentativas<x-admin.help-icon :text="$helpTexts['retry_attempts']" label="Ajuda sobre retentativas" /></label><input class="form-control" id="retry_attempts" name="retry_attempts" type="number" min="0" max="20" value="{{ old('retry_attempts', $setting->retry_attempts) }}" required></div>
            <div class="col-md-3"><label class="form-label" for="retry_backoff_seconds">Backoff<x-admin.help-icon :text="$helpTexts['retry_backoff_seconds']" label="Ajuda sobre backoff" /></label><input class="form-control" id="retry_backoff_seconds" name="retry_backoff_seconds" type="number" min="0" max="3600" value="{{ old('retry_backoff_seconds', $setting->retry_backoff_seconds) }}" required></div>
            <div class="col-md-3"><label class="form-label" for="circuit_breaker_failure_threshold">Threshold<x-admin.help-icon :text="$helpTexts['circuit_breaker_failure_threshold']" label="Ajuda sobre threshold" /></label><input class="form-control" id="circuit_breaker_failure_threshold" name="circuit_breaker_failure_threshold" type="number" min="1" max="100" value="{{ old('circuit_breaker_failure_threshold', $setting->circuit_breaker_failure_threshold) }}" required></div>
            <div class="col-md-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="integration_enabled" name="integration_enabled" value="1" @checked(old('integration_enabled', $setting->integration_enabled))><label class="form-check-label" for="integration_enabled">Integrações habilitadas<x-admin.help-icon :text="$helpTexts['integration_enabled']" label="Ajuda sobre integrações habilitadas" /></label></div></div>
            <div class="col-md-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="circuit_breaker_enabled" name="circuit_breaker_enabled" value="1" @checked(old('circuit_breaker_enabled', $setting->circuit_breaker_enabled))><label class="form-check-label" for="circuit_breaker_enabled">Circuit breaker<x-admin.help-icon :text="$helpTexts['circuit_breaker_enabled']" label="Ajuda sobre circuit breaker" /></label></div></div>
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
