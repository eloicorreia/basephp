@extends('layouts.admin')

@section('title', 'E-mail do Tenant')
@section('page-title', 'Configurações do Sistema')

@php
    $helpTexts = [
        'name' => 'Nome interno para identificar esta configuração SMTP do tenant.',
        'host' => 'Servidor SMTP usado para enviar e-mails transacionais e administrativos do tenant.',
        'port' => 'Porta de conexão do SMTP. Normalmente 587 para TLS, 465 para SSL ou 25 para conexões sem criptografia.',
        'encryption' => 'Tipo de criptografia usado na conexão com o SMTP.',
        'timeout_seconds' => 'Tempo máximo de espera, em segundos, para conectar e enviar mensagens pelo SMTP.',
        'is_active' => 'Define se esta configuração pode ser usada pelos envios de e-mail do tenant.',
        'username' => 'Usuário de autenticação no servidor SMTP, quando exigido pelo provedor.',
        'password' => 'Senha ou token de autenticação SMTP. Se ficar em branco, a senha existente é mantida.',
        'from_address' => 'E-mail remetente usado nas mensagens enviadas pelo tenant.',
        'from_name' => 'Nome exibido junto ao e-mail remetente nas caixas de entrada.',
        'reply_to_address' => 'Endereço que recebe respostas dos destinatários. Se vazio, as respostas usam o remetente.',
        'reply_to_name' => 'Nome exibido para o endereço de resposta.',
        'verify_peer' => 'Valida a cadeia do certificado TLS do servidor SMTP.',
        'verify_peer_name' => 'Confere se o nome do certificado corresponde ao host SMTP configurado.',
        'allow_self_signed' => 'Permite certificado autoassinado. Use apenas em ambientes controlados ou homologação.',
        'to' => 'Destinatário usado para enviar um e-mail de teste com a configuração atual.',
    ];
@endphp

@section('content')
    <x-admin.page-title
        title="E-mail"
        subtitle="Configuração SMTP padrão usada pelos envios do tenant."
        aside="Tenant"
    />

    @include('admin.system-settings._alerts')
    @include('admin.system-settings._tenant-selector', [
        'tenants' => $tenants,
        'selectedTenant' => $selectedTenant,
        'routeName' => 'admin.system-settings.mail.edit',
    ])
    @include('admin.system-settings._load-state')

    @if ($selectedTenant && empty($loadError))
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.system-settings.mail.update') }}" class="row g-3">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="tenant" value="{{ $selectedTenant->code }}">

                    <div class="col-md-6">
                        <label class="form-label" for="name">Nome da configuração<x-admin.help-icon :text="$helpTexts['name']" label="Ajuda sobre nome da configuração" /></label>
                        <input class="form-control" id="name" name="name" value="{{ old('name', $mailConfig?->name ?? 'SMTP padrão') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="host">Host SMTP<x-admin.help-icon :text="$helpTexts['host']" label="Ajuda sobre host SMTP" /></label>
                        <input class="form-control" id="host" name="host" value="{{ old('host', $mailConfig?->host) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="port">Porta<x-admin.help-icon :text="$helpTexts['port']" label="Ajuda sobre porta SMTP" /></label>
                        <input class="form-control" id="port" name="port" type="number" min="1" max="65535" value="{{ old('port', $mailConfig?->port ?? 587) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="encryption">Criptografia<x-admin.help-icon :text="$helpTexts['encryption']" label="Ajuda sobre criptografia SMTP" /></label>
                        <select class="form-select" id="encryption" name="encryption">
                            <option value="" @selected(old('encryption', $mailConfig?->encryption) === null)>Nenhuma</option>
                            <option value="tls" @selected(old('encryption', $mailConfig?->encryption) === 'tls')>TLS</option>
                            <option value="ssl" @selected(old('encryption', $mailConfig?->encryption) === 'ssl')>SSL</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="timeout_seconds">Timeout<x-admin.help-icon :text="$helpTexts['timeout_seconds']" label="Ajuda sobre timeout SMTP" /></label>
                        <input class="form-control" id="timeout_seconds" name="timeout_seconds" type="number" min="1" max="300" value="{{ old('timeout_seconds', $mailConfig?->timeout_seconds ?? 30) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status<x-admin.help-icon :text="$helpTexts['is_active']" label="Ajuda sobre status do SMTP" /></label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $mailConfig?->is_active ?? true))>
                            <label class="form-check-label" for="is_active">Ativo</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="username">Usuário SMTP<x-admin.help-icon :text="$helpTexts['username']" label="Ajuda sobre usuário SMTP" /></label>
                        <input class="form-control" id="username" name="username" value="{{ old('username', $mailConfig?->username) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password">Senha SMTP<x-admin.help-icon :text="$helpTexts['password']" label="Ajuda sobre senha SMTP" /></label>
                        <input class="form-control" id="password" name="password" type="password" autocomplete="new-password" placeholder="{{ $mailConfig?->password_encrypted ? 'Mantida se ficar em branco' : '' }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="from_address">Remetente<x-admin.help-icon :text="$helpTexts['from_address']" label="Ajuda sobre remetente" /></label>
                        <input class="form-control" id="from_address" name="from_address" type="email" value="{{ old('from_address', $mailConfig?->from_address) }}" required>
                        <div class="invalid-feedback">Informe um e-mail válido.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="from_name">Nome do remetente<x-admin.help-icon :text="$helpTexts['from_name']" label="Ajuda sobre nome do remetente" /></label>
                        <input class="form-control" id="from_name" name="from_name" value="{{ old('from_name', $mailConfig?->from_name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="reply_to_address">Reply-to<x-admin.help-icon :text="$helpTexts['reply_to_address']" label="Ajuda sobre reply-to" /></label>
                        <input class="form-control" id="reply_to_address" name="reply_to_address" type="email" value="{{ old('reply_to_address', $mailConfig?->reply_to_address) }}">
                        <div class="invalid-feedback">Informe um e-mail válido.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="reply_to_name">Nome reply-to<x-admin.help-icon :text="$helpTexts['reply_to_name']" label="Ajuda sobre nome reply-to" /></label>
                        <input class="form-control" id="reply_to_name" name="reply_to_name" value="{{ old('reply_to_name', $mailConfig?->reply_to_name) }}">
                    </div>

                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="verify_peer" name="verify_peer" value="1" @checked(old('verify_peer', $mailConfig?->verify_peer ?? true))>
                            <label class="form-check-label" for="verify_peer">Verificar certificado<x-admin.help-icon :text="$helpTexts['verify_peer']" label="Ajuda sobre verificação de certificado" /></label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="verify_peer_name" name="verify_peer_name" value="1" @checked(old('verify_peer_name', $mailConfig?->verify_peer_name ?? true))>
                            <label class="form-check-label" for="verify_peer_name">Verificar nome do certificado<x-admin.help-icon :text="$helpTexts['verify_peer_name']" label="Ajuda sobre nome do certificado" /></label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="allow_self_signed" name="allow_self_signed" value="1" @checked(old('allow_self_signed', $mailConfig?->allow_self_signed ?? false))>
                            <label class="form-check-label" for="allow_self_signed">Permitir self-signed<x-admin.help-icon :text="$helpTexts['allow_self_signed']" label="Ajuda sobre certificado autoassinado" /></label>
                        </div>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a class="btn btn-light" href="{{ route('admin.system-settings.index', ['tenant' => $selectedTenant->code]) }}">Voltar</a>
                        <button class="btn btn-primary" type="submit">Salvar e-mail</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($mailConfig)
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.system-settings.mail.test') }}" class="row g-3 align-items-end">
                        @csrf
                        <input type="hidden" name="tenant" value="{{ $selectedTenant->code }}">
                        <div class="col-md-8">
                            <label class="form-label" for="to">Enviar teste para<x-admin.help-icon :text="$helpTexts['to']" label="Ajuda sobre envio de teste" /></label>
                            <input class="form-control" id="to" name="to" type="email" value="{{ old('to') }}" required>
                            <div class="invalid-feedback">Informe um e-mail válido.</div>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-outline-primary w-100" type="submit">Enviar teste</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
                new bootstrap.Tooltip(element);
            });

            document.querySelectorAll('input[type="email"]').forEach((email) => {
                email.addEventListener('blur', () => {
                    email.classList.toggle('is-invalid', email.value !== '' && !email.checkValidity());
                });
            });
        });
    </script>
@endpush
