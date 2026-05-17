@extends('layouts.admin')

@section('title', 'Notificações')
@section('page-title', 'Configurações do Sistema')

@section('content')
    <x-admin.page-title title="Notificações" subtitle="Alertas administrativos por tenant." aside="Tenant" />
    @include('admin.system-settings._alerts')
    @include('admin.system-settings._tenant-selector', ['tenants' => $tenants, 'selectedTenant' => $selectedTenant, 'routeName' => 'admin.system-settings.notifications.edit'])
    @include('admin.system-settings._load-state')
    @if ($selectedTenant && $setting && empty($loadError))
        <div class="card"><div class="card-body"><form method="POST" action="{{ route('admin.system-settings.notifications.update') }}" class="row g-3">
            @csrf @method('PUT')<input type="hidden" name="tenant" value="{{ $selectedTenant->code }}">
            @foreach (['notify_admin_on_failed_jobs' => 'Falha em jobs', 'notify_admin_on_email_failure' => 'Falha de e-mail', 'notify_admin_on_permission_change' => 'Mudança de permissão', 'notify_admin_on_integration_failure' => 'Falha de integração'] as $field => $label)
                <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="{{ $field }}" name="{{ $field }}" value="1" @checked(old($field, $setting->{$field}))><label class="form-check-label" for="{{ $field }}">{{ $label }}</label></div></div>
            @endforeach
            <div class="col-12"><label class="form-label" for="admin_notification_emails">E-mails administrativos</label><textarea class="form-control" id="admin_notification_emails" name="admin_notification_emails" rows="4">{{ old('admin_notification_emails', implode("\n", $setting->admin_notification_emails ?? [])) }}</textarea></div>
            <div class="col-12 d-flex justify-content-end gap-2"><a class="btn btn-light" href="{{ route('admin.system-settings.index', ['tenant' => $selectedTenant->code]) }}">Voltar</a><button class="btn btn-primary" type="submit">Salvar</button></div>
        </form></div></div>
    @endif
@endsection
