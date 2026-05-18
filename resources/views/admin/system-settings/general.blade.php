@extends('layouts.admin')

@section('title', 'Configurações Gerais')
@section('page-title', 'Configurações do Sistema')

@php
    use App\Http\Requests\Web\Admin\SystemSettings\UpdateTenantSystemSettingsRequest;

    $timezoneOptions = DateTimeZone::listIdentifiers();
    $localeOptions = UpdateTenantSystemSettingsRequest::localeOptions();
    $dateFormatOptions = UpdateTenantSystemSettingsRequest::dateFormatOptions();
    $datetimeFormatOptions = UpdateTenantSystemSettingsRequest::datetimeFormatOptions();
    $helpTexts = [
        'timezone' => 'Define o fuso horário usado pelo tenant para calcular vencimentos, registros operacionais, logs e horários exibidos nas telas.',
        'locale' => 'Define o idioma e a cultura padrão usados em formatação, mensagens e futuras traduções do tenant.',
        'date_format' => 'Define como datas simples são exibidas para os usuários. O padrão brasileiro é dd/mm/aaaa.',
        'datetime_format' => 'Define como datas com horário são exibidas. Use o padrão brasileiro com horário de 24 horas.',
        'default_items_per_page' => 'Quantidade inicial de registros exibidos em listagens e consultas paginadas deste tenant.',
        'max_items_per_page' => 'Limite máximo permitido por página para evitar consultas pesadas e manter a tela responsiva.',
        'support_email' => 'Endereço usado como contato de suporte do tenant em avisos, mensagens administrativas e comunicações operacionais.',
        'support_phone' => 'Telefone de contato do suporte do tenant, exibido em comunicações e usado como referência operacional.',
        'maintenance_mode' => 'Ativa a indicação de manutenção para sinalizar indisponibilidade ou operação assistida no tenant.',
        'maintenance_message' => 'Mensagem exibida durante manutenção. Aceita HTML para destacar links, listas e instruções formatadas.',
    ];

    $helpIcon = static function (string $field) use ($helpTexts): string {
        return '<button type="button" class="btn btn-link p-0 ms-1 text-muted align-baseline system-setting-help" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="'.e($helpTexts[$field]).'" aria-label="Ajuda sobre '.$field.'">?</button>';
    };

    $formatPhone = static function (?string $phone): ?string {
        if ($phone === null || $phone === '') {
            return $phone;
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (strlen($digits) === 11) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7, 4));
        }

        if (strlen($digits) === 10) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6, 4));
        }

        return $phone;
    };
@endphp

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

                <div class="col-md-4">
                    <label class="form-label" for="timezone">Timezone{!! $helpIcon('timezone') !!}</label>
                    <select class="form-select" id="timezone" name="timezone" required>
                        @foreach ($timezoneOptions as $timezone)
                            <option value="{{ $timezone }}" @selected(old('timezone', $setting->timezone) === $timezone)>{{ $timezone }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="locale">Locale{!! $helpIcon('locale') !!}</label>
                    <select class="form-select" id="locale" name="locale" required>
                        @foreach ($localeOptions as $locale => $label)
                            <option value="{{ $locale }}" @selected(old('locale', $setting->locale) === $locale)>{{ $label }} ({{ $locale }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="date_format">Data{!! $helpIcon('date_format') !!}</label>
                    <select class="form-select" id="date_format" name="date_format" required>
                        @foreach ($dateFormatOptions as $format => $label)
                            <option value="{{ $format }}" @selected(old('date_format', $setting->date_format) === $format)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="datetime_format">Data/hora{!! $helpIcon('datetime_format') !!}</label>
                    <select class="form-select" id="datetime_format" name="datetime_format" required>
                        @foreach ($datetimeFormatOptions as $format => $label)
                            <option value="{{ $format }}" @selected(old('datetime_format', $setting->datetime_format) === $format)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="default_items_per_page">Itens padrão{!! $helpIcon('default_items_per_page') !!}</label>
                    <input class="form-control" id="default_items_per_page" name="default_items_per_page" type="number" min="1" max="500" value="{{ old('default_items_per_page', $setting->default_items_per_page) }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="max_items_per_page">Itens máximo{!! $helpIcon('max_items_per_page') !!}</label>
                    <input class="form-control" id="max_items_per_page" name="max_items_per_page" type="number" min="1" max="1000" value="{{ old('max_items_per_page', $setting->max_items_per_page) }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="support_email">E-mail suporte{!! $helpIcon('support_email') !!}</label>
                    <input class="form-control" id="support_email" name="support_email" type="email" inputmode="email" value="{{ old('support_email', $setting->support_email) }}">
                    <div class="invalid-feedback">Informe um e-mail válido.</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="support_phone">Telefone suporte{!! $helpIcon('support_phone') !!}</label>
                    <input class="form-control" id="support_phone" name="support_phone" inputmode="tel" maxlength="15" placeholder="(11) 99999-9999" value="{{ $formatPhone(old('support_phone', $setting->support_phone)) }}">
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1" @checked(old('maintenance_mode', $setting->maintenance_mode))>
                        <label class="form-check-label" for="maintenance_mode">Modo manutenção{!! $helpIcon('maintenance_mode') !!}</label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label" for="maintenance_message">Mensagem de manutenção{!! $helpIcon('maintenance_message') !!}</label>
                    <textarea class="form-control font-monospace" id="maintenance_message" name="maintenance_message" rows="5" placeholder="<strong>Manutenção programada</strong><br>Voltaremos às 18:00.">{{ old('maintenance_message', $setting->maintenance_message) }}</textarea>
                </div>

                <div class="col-12 d-flex justify-content-end gap-2"><a class="btn btn-light" href="{{ route('admin.system-settings.index', ['tenant' => $selectedTenant->code]) }}">Voltar</a><button class="btn btn-primary" type="submit">Salvar</button></div>
            </form>
        </div></div>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
                new bootstrap.Tooltip(element);
            });

            const email = document.getElementById('support_email');

            if (email) {
                email.addEventListener('blur', () => {
                    email.classList.toggle('is-invalid', email.value !== '' && !email.checkValidity());
                });
            }

            const phone = document.getElementById('support_phone');

            if (phone) {
                phone.addEventListener('input', () => {
                    const digits = phone.value.replace(/\D/g, '').slice(0, 11);
                    const areaCode = digits.slice(0, 2);
                    const firstPart = digits.length > 10 ? digits.slice(2, 7) : digits.slice(2, 6);
                    const secondPart = digits.length > 10 ? digits.slice(7, 11) : digits.slice(6, 10);

                    phone.value = [
                        areaCode ? `(${areaCode}` : '',
                        areaCode.length === 2 ? ') ' : '',
                        firstPart,
                        secondPart ? `-${secondPart}` : '',
                    ].join('');
                });
            }
        });
    </script>
@endpush
