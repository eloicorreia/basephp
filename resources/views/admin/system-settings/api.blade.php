@extends('layouts.admin')

@section('title', 'API do Tenant')
@section('page-title', 'Configurações do Sistema')

@section('content')
    <x-admin.page-title title="API" subtitle="Rate limits, paginação e origens permitidas." aside="Tenant" />
    @include('admin.system-settings._alerts')
    @include('admin.system-settings._tenant-selector', ['tenants' => $tenants, 'selectedTenant' => $selectedTenant, 'routeName' => 'admin.system-settings.api.edit'])
    @include('admin.system-settings._load-state')
    @if ($selectedTenant && $setting && empty($loadError))
        <div class="card"><div class="card-body"><form method="POST" action="{{ route('admin.system-settings.api.update') }}" class="row g-3">
            @csrf @method('PUT')<input type="hidden" name="tenant" value="{{ $selectedTenant->code }}">
            <div class="col-md-4"><label class="form-label" for="api_rate_limit_per_minute">API/min</label><input class="form-control" id="api_rate_limit_per_minute" name="api_rate_limit_per_minute" type="number" value="{{ old('api_rate_limit_per_minute', $setting->api_rate_limit_per_minute) }}" required></div>
            <div class="col-md-4"><label class="form-label" for="strict_rate_limit_per_minute">Strict/min</label><input class="form-control" id="strict_rate_limit_per_minute" name="strict_rate_limit_per_minute" type="number" value="{{ old('strict_rate_limit_per_minute', $setting->strict_rate_limit_per_minute) }}" required></div>
            <div class="col-md-4"><label class="form-label" for="login_rate_limit_per_minute">Login/min</label><input class="form-control" id="login_rate_limit_per_minute" name="login_rate_limit_per_minute" type="number" value="{{ old('login_rate_limit_per_minute', $setting->login_rate_limit_per_minute) }}" required></div>
            <div class="col-md-6"><label class="form-label" for="api_default_pagination_size">Paginação padrão</label><input class="form-control" id="api_default_pagination_size" name="api_default_pagination_size" type="number" value="{{ old('api_default_pagination_size', $setting->api_default_pagination_size) }}" required></div>
            <div class="col-md-6"><label class="form-label" for="api_max_pagination_size">Paginação máxima</label><input class="form-control" id="api_max_pagination_size" name="api_max_pagination_size" type="number" value="{{ old('api_max_pagination_size', $setting->api_max_pagination_size) }}" required></div>
            <div class="col-md-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="api_enabled" name="api_enabled" value="1" @checked(old('api_enabled', $setting->api_enabled))><label class="form-check-label" for="api_enabled">API habilitada</label></div></div>
            <div class="col-md-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="api_require_correlation_id" name="api_require_correlation_id" value="1" @checked(old('api_require_correlation_id', $setting->api_require_correlation_id))><label class="form-check-label" for="api_require_correlation_id">Exigir correlation id</label></div></div>
            <div class="col-12"><label class="form-label" for="api_allowed_origins">Origens permitidas</label><textarea class="form-control" id="api_allowed_origins" name="api_allowed_origins" rows="4">{{ old('api_allowed_origins', implode("\n", $setting->api_allowed_origins ?? [])) }}</textarea></div>
            <div class="col-12 d-flex justify-content-end gap-2"><a class="btn btn-light" href="{{ route('admin.system-settings.index', ['tenant' => $selectedTenant->code]) }}">Voltar</a><button class="btn btn-primary" type="submit">Salvar</button></div>
        </form></div></div>
    @endif
@endsection
