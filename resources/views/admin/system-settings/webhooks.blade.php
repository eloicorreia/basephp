@extends('layouts.admin')

@section('title', 'Webhooks')
@section('page-title', 'Configurações do Sistema')

@php
    $helpTexts = [
        'webhook_url' => 'Endpoint HTTPS que receberá notificações de eventos do tenant.',
        'webhook_secret' => 'Segredo usado para assinar ou validar entregas do webhook. Se ficar em branco, o segredo atual é mantido.',
        'webhook_retry_attempts' => 'Quantidade de novas tentativas quando uma entrega de webhook falhar.',
        'webhook_timeout_seconds' => 'Tempo máximo, em segundos, para aguardar resposta do endpoint do webhook.',
        'webhook_events' => 'Eventos que disparam o webhook. Informe um por linha usando nomes como invoice.created ou user.updated.',
        'webhook_enabled' => 'Habilita ou desabilita o envio de webhooks para este tenant.',
    ];
@endphp

@section('content')
    <x-admin.page-title title="Webhooks" subtitle="Endpoint, segredo e eventos por tenant." aside="Tenant" />
    @include('admin.system-settings._alerts')
    @include('admin.system-settings._tenant-selector', ['tenants' => $tenants, 'selectedTenant' => $selectedTenant, 'routeName' => 'admin.system-settings.webhooks.edit'])
    @include('admin.system-settings._load-state')
    @if ($selectedTenant && $setting && empty($loadError))
        <div class="card"><div class="card-body"><form method="POST" action="{{ route('admin.system-settings.webhooks.update') }}" class="row g-3">
            @csrf @method('PUT')<input type="hidden" name="tenant" value="{{ $selectedTenant->code }}">
            <div class="col-md-8"><label class="form-label" for="webhook_url">URL<x-admin.help-icon :text="$helpTexts['webhook_url']" label="Ajuda sobre URL do webhook" /></label><input class="form-control" id="webhook_url" name="webhook_url" type="url" value="{{ old('webhook_url', $setting->webhook_url) }}"></div>
            <div class="col-md-4"><label class="form-label" for="webhook_secret">Segredo<x-admin.help-icon :text="$helpTexts['webhook_secret']" label="Ajuda sobre segredo do webhook" /></label><input class="form-control" id="webhook_secret" name="webhook_secret" type="password" autocomplete="new-password" placeholder="{{ $setting->webhook_secret_encrypted ? 'Mantido se ficar em branco' : '' }}"></div>
            <div class="col-md-6"><label class="form-label" for="webhook_retry_attempts">Retentativas<x-admin.help-icon :text="$helpTexts['webhook_retry_attempts']" label="Ajuda sobre retentativas do webhook" /></label><input class="form-control" id="webhook_retry_attempts" name="webhook_retry_attempts" type="number" min="0" max="20" value="{{ old('webhook_retry_attempts', $setting->webhook_retry_attempts) }}" required></div>
            <div class="col-md-6"><label class="form-label" for="webhook_timeout_seconds">Timeout<x-admin.help-icon :text="$helpTexts['webhook_timeout_seconds']" label="Ajuda sobre timeout do webhook" /></label><input class="form-control" id="webhook_timeout_seconds" name="webhook_timeout_seconds" type="number" min="1" max="300" value="{{ old('webhook_timeout_seconds', $setting->webhook_timeout_seconds) }}" required></div>
            <div class="col-12"><label class="form-label" for="webhook_events">Eventos<x-admin.help-icon :text="$helpTexts['webhook_events']" label="Ajuda sobre eventos do webhook" /></label><textarea class="form-control font-monospace" id="webhook_events" name="webhook_events" rows="4" placeholder="invoice.created&#10;user.updated">{{ old('webhook_events', implode("\n", $setting->webhook_events ?? [])) }}</textarea></div>
            <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="webhook_enabled" name="webhook_enabled" value="1" @checked(old('webhook_enabled', $setting->webhook_enabled))><label class="form-check-label" for="webhook_enabled">Webhook habilitado<x-admin.help-icon :text="$helpTexts['webhook_enabled']" label="Ajuda sobre webhook habilitado" /></label></div></div>
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
