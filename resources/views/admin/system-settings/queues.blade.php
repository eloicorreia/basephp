@extends('layouts.admin')

@section('title', 'Filas do Tenant')
@section('page-title', 'Configurações do Sistema')

@section('content')
    <x-admin.page-title title="Filas" subtitle="Queues, tentativas e processamento." aside="Tenant" />
    @include('admin.system-settings._alerts')
    @include('admin.system-settings._tenant-selector', ['tenants' => $tenants, 'selectedTenant' => $selectedTenant, 'routeName' => 'admin.system-settings.queues.edit'])
    @include('admin.system-settings._load-state')
    @if ($selectedTenant && $setting && empty($loadError))
        <div class="card"><div class="card-body"><form method="POST" action="{{ route('admin.system-settings.queues.update') }}" class="row g-3">
            @csrf @method('PUT')<input type="hidden" name="tenant" value="{{ $selectedTenant->code }}">
            <div class="col-md-6"><label class="form-label" for="default_queue">Fila padrão</label><input class="form-control" id="default_queue" name="default_queue" value="{{ old('default_queue', $setting->default_queue) }}" required></div>
            <div class="col-md-6"><label class="form-label" for="email_queue">Fila de e-mail</label><input class="form-control" id="email_queue" name="email_queue" value="{{ old('email_queue', $setting->email_queue) }}" required></div>
            <div class="col-md-6"><label class="form-label" for="max_job_attempts">Tentativas</label><input class="form-control" id="max_job_attempts" name="max_job_attempts" type="number" value="{{ old('max_job_attempts', $setting->max_job_attempts) }}" required></div>
            <div class="col-md-6"><label class="form-label" for="job_retry_delay_seconds">Delay retry (s)</label><input class="form-control" id="job_retry_delay_seconds" name="job_retry_delay_seconds" type="number" value="{{ old('job_retry_delay_seconds', $setting->job_retry_delay_seconds) }}" required></div>
            <div class="col-md-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="failed_job_notify_admin" name="failed_job_notify_admin" value="1" @checked(old('failed_job_notify_admin', $setting->failed_job_notify_admin))><label class="form-check-label" for="failed_job_notify_admin">Notificar admin em falhas</label></div></div>
            <div class="col-md-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="queue_processing_enabled" name="queue_processing_enabled" value="1" @checked(old('queue_processing_enabled', $setting->queue_processing_enabled))><label class="form-check-label" for="queue_processing_enabled">Processamento habilitado</label></div></div>
            <div class="col-12 d-flex justify-content-end gap-2"><a class="btn btn-light" href="{{ route('admin.system-settings.index', ['tenant' => $selectedTenant->code]) }}">Voltar</a><button class="btn btn-primary" type="submit">Salvar</button></div>
        </form></div></div>
    @endif
@endsection
