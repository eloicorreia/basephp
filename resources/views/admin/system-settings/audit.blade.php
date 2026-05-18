@extends('layouts.admin')

@section('title', 'Logs e Auditoria')
@section('page-title', 'Configurações do Sistema')

@php
    $helpTexts = [
        'audit_enabled' => 'Habilita o registro de auditoria para eventos administrativos e operacionais do tenant.',
        'audit_store_before_after' => 'Armazena valores antes e depois das alterações auditadas para facilitar investigação.',
        'audit_payload_enabled' => 'Inclui payloads relevantes nos registros de auditoria quando permitido.',
        'audit_sensitive_payload_masking' => 'Mascara dados sensíveis nos payloads antes de gravar logs.',
        'api_request_log_retention_days' => 'Quantidade de dias para manter logs de requisições da API.',
        'audit_log_retention_days' => 'Quantidade de dias para manter registros de auditoria.',
        'integration_log_retention_days' => 'Quantidade de dias para manter logs de integrações externas.',
        'email_log_retention_days' => 'Quantidade de dias para manter logs de envio e falha de e-mail.',
        'queue_log_retention_days' => 'Quantidade de dias para manter logs de execução de filas e jobs.',
    ];
@endphp

@section('content')
    <x-admin.page-title title="Logs e Auditoria" subtitle="Auditoria e retenção por tenant." aside="Tenant" />
    @include('admin.system-settings._alerts')
    @include('admin.system-settings._tenant-selector', ['tenants' => $tenants, 'selectedTenant' => $selectedTenant, 'routeName' => 'admin.system-settings.audit.edit'])
    @include('admin.system-settings._load-state')
    @if ($selectedTenant && $setting && empty($loadError))
        <div class="card"><div class="card-body"><form method="POST" action="{{ route('admin.system-settings.audit.update') }}" class="row g-3">
            @csrf @method('PUT')<input type="hidden" name="tenant" value="{{ $selectedTenant->code }}">
            @foreach (['audit_enabled' => 'Auditoria habilitada', 'audit_store_before_after' => 'Guardar before/after', 'audit_payload_enabled' => 'Auditar payload', 'audit_sensitive_payload_masking' => 'Mascarar sensíveis'] as $field => $label)
                <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="{{ $field }}" name="{{ $field }}" value="1" @checked(old($field, $setting->{$field}))><label class="form-check-label" for="{{ $field }}">{{ $label }}<x-admin.help-icon :text="$helpTexts[$field]" label="Ajuda sobre {{ $label }}" /></label></div></div>
            @endforeach
            @foreach (['api_request_log_retention_days' => 'Retenção API', 'audit_log_retention_days' => 'Retenção auditoria', 'integration_log_retention_days' => 'Retenção integrações', 'email_log_retention_days' => 'Retenção e-mail', 'queue_log_retention_days' => 'Retenção filas'] as $field => $label)
                <div class="col-md"><label class="form-label" for="{{ $field }}">{{ $label }}<x-admin.help-icon :text="$helpTexts[$field]" label="Ajuda sobre {{ $label }}" /></label><input class="form-control" id="{{ $field }}" name="{{ $field }}" type="number" min="1" max="3650" value="{{ old($field, $setting->{$field}) }}" required></div>
            @endforeach
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
