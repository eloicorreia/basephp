@extends('layouts.admin')

@section('title', 'Integrações')
@section('page-title', 'Configurações do Sistema')

@section('content')
    <x-admin.page-title title="Integrações" subtitle="Timeouts, retentativas e circuit breaker." aside="Tenant" />
    @include('admin.system-settings._alerts')
    @include('admin.system-settings._tenant-selector', ['tenants' => $tenants, 'selectedTenant' => $selectedTenant, 'routeName' => 'admin.system-settings.integrations.edit'])
    @include('admin.system-settings._load-state')
    @if ($selectedTenant && $setting && empty($loadError))
        <div class="card"><div class="card-body"><form method="POST" action="{{ route('admin.system-settings.integrations.update') }}" class="row g-3">
            @csrf @method('PUT')<input type="hidden" name="tenant" value="{{ $selectedTenant->code }}">
            <div class="col-md-3"><label class="form-label" for="default_timeout_seconds">Timeout</label><input class="form-control" id="default_timeout_seconds" name="default_timeout_seconds" type="number" value="{{ old('default_timeout_seconds', $setting->default_timeout_seconds) }}" required></div>
            <div class="col-md-3"><label class="form-label" for="retry_attempts">Retentativas</label><input class="form-control" id="retry_attempts" name="retry_attempts" type="number" value="{{ old('retry_attempts', $setting->retry_attempts) }}" required></div>
            <div class="col-md-3"><label class="form-label" for="retry_backoff_seconds">Backoff</label><input class="form-control" id="retry_backoff_seconds" name="retry_backoff_seconds" type="number" value="{{ old('retry_backoff_seconds', $setting->retry_backoff_seconds) }}" required></div>
            <div class="col-md-3"><label class="form-label" for="circuit_breaker_failure_threshold">Threshold</label><input class="form-control" id="circuit_breaker_failure_threshold" name="circuit_breaker_failure_threshold" type="number" value="{{ old('circuit_breaker_failure_threshold', $setting->circuit_breaker_failure_threshold) }}" required></div>
            <div class="col-md-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="integration_enabled" name="integration_enabled" value="1" @checked(old('integration_enabled', $setting->integration_enabled))><label class="form-check-label" for="integration_enabled">Integrações habilitadas</label></div></div>
            <div class="col-md-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="circuit_breaker_enabled" name="circuit_breaker_enabled" value="1" @checked(old('circuit_breaker_enabled', $setting->circuit_breaker_enabled))><label class="form-check-label" for="circuit_breaker_enabled">Circuit breaker</label></div></div>
            <div class="col-12 d-flex justify-content-end gap-2"><a class="btn btn-light" href="{{ route('admin.system-settings.index', ['tenant' => $selectedTenant->code]) }}">Voltar</a><button class="btn btn-primary" type="submit">Salvar</button></div>
        </form></div></div>
    @endif
@endsection
