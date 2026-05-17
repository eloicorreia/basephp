@extends('layouts.admin')

@section('title', 'Configurações Gerais')
@section('page-title', 'Configurações do Sistema')

@section('content')
    <x-admin.page-title title="Geral" subtitle="Localização, paginação, suporte e manutenção por tenant." aside="Tenant" />
    @include('admin.system-settings._alerts')
    @include('admin.system-settings._tenant-selector', ['tenants' => $tenants, 'selectedTenant' => $selectedTenant, 'routeName' => 'admin.system-settings.general.edit'])
    @include('admin.system-settings._load-state')

    @if ($selectedTenant && $setting && empty($loadError))
        <div class="card"><div class="card-body">
            <form method="POST" action="{{ route('admin.system-settings.general.update') }}" class="row g-3">
                @csrf @method('PUT')
                <input type="hidden" name="tenant" value="{{ $selectedTenant->code }}">
                <div class="col-md-4"><label class="form-label" for="timezone">Timezone</label><input class="form-control" id="timezone" name="timezone" value="{{ old('timezone', $setting->timezone) }}" required></div>
                <div class="col-md-4"><label class="form-label" for="locale">Locale</label><input class="form-control" id="locale" name="locale" value="{{ old('locale', $setting->locale) }}" required></div>
                <div class="col-md-2"><label class="form-label" for="date_format">Data</label><input class="form-control" id="date_format" name="date_format" value="{{ old('date_format', $setting->date_format) }}" required></div>
                <div class="col-md-2"><label class="form-label" for="datetime_format">Data/hora</label><input class="form-control" id="datetime_format" name="datetime_format" value="{{ old('datetime_format', $setting->datetime_format) }}" required></div>
                <div class="col-md-3"><label class="form-label" for="default_items_per_page">Itens padrão</label><input class="form-control" id="default_items_per_page" name="default_items_per_page" type="number" value="{{ old('default_items_per_page', $setting->default_items_per_page) }}" required></div>
                <div class="col-md-3"><label class="form-label" for="max_items_per_page">Itens máximo</label><input class="form-control" id="max_items_per_page" name="max_items_per_page" type="number" value="{{ old('max_items_per_page', $setting->max_items_per_page) }}" required></div>
                <div class="col-md-3"><label class="form-label" for="support_email">E-mail suporte</label><input class="form-control" id="support_email" name="support_email" type="email" value="{{ old('support_email', $setting->support_email) }}"></div>
                <div class="col-md-3"><label class="form-label" for="support_phone">Telefone suporte</label><input class="form-control" id="support_phone" name="support_phone" value="{{ old('support_phone', $setting->support_phone) }}"></div>
                <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1" @checked(old('maintenance_mode', $setting->maintenance_mode))><label class="form-check-label" for="maintenance_mode">Modo manutenção</label></div></div>
                <div class="col-12"><label class="form-label" for="maintenance_message">Mensagem de manutenção</label><textarea class="form-control" id="maintenance_message" name="maintenance_message" rows="3">{{ old('maintenance_message', $setting->maintenance_message) }}</textarea></div>
                <div class="col-12 d-flex justify-content-end gap-2"><a class="btn btn-light" href="{{ route('admin.system-settings.index', ['tenant' => $selectedTenant->code]) }}">Voltar</a><button class="btn btn-primary" type="submit">Salvar</button></div>
            </form>
        </div></div>
    @endif
@endsection
