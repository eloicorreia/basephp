@extends('layouts.admin')

@section('title', 'E-mail do Tenant')
@section('page-title', 'Configurações do Sistema')

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
                        <label class="form-label" for="name">Nome da configuração</label>
                        <input class="form-control" id="name" name="name" value="{{ old('name', $mailConfig?->name ?? 'SMTP padrão') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="host">Host SMTP</label>
                        <input class="form-control" id="host" name="host" value="{{ old('host', $mailConfig?->host) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="port">Porta</label>
                        <input class="form-control" id="port" name="port" type="number" min="1" max="65535" value="{{ old('port', $mailConfig?->port ?? 587) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="encryption">Criptografia</label>
                        <select class="form-select" id="encryption" name="encryption">
                            <option value="" @selected(old('encryption', $mailConfig?->encryption) === null)>Nenhuma</option>
                            <option value="tls" @selected(old('encryption', $mailConfig?->encryption) === 'tls')>TLS</option>
                            <option value="ssl" @selected(old('encryption', $mailConfig?->encryption) === 'ssl')>SSL</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="timeout_seconds">Timeout</label>
                        <input class="form-control" id="timeout_seconds" name="timeout_seconds" type="number" min="1" max="300" value="{{ old('timeout_seconds', $mailConfig?->timeout_seconds ?? 30) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $mailConfig?->is_active ?? true))>
                            <label class="form-check-label" for="is_active">Ativo</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="username">Usuário SMTP</label>
                        <input class="form-control" id="username" name="username" value="{{ old('username', $mailConfig?->username) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password">Senha SMTP</label>
                        <input class="form-control" id="password" name="password" type="password" autocomplete="new-password" placeholder="{{ $mailConfig?->password_encrypted ? 'Mantida se ficar em branco' : '' }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="from_address">Remetente</label>
                        <input class="form-control" id="from_address" name="from_address" type="email" value="{{ old('from_address', $mailConfig?->from_address) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="from_name">Nome do remetente</label>
                        <input class="form-control" id="from_name" name="from_name" value="{{ old('from_name', $mailConfig?->from_name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="reply_to_address">Reply-to</label>
                        <input class="form-control" id="reply_to_address" name="reply_to_address" type="email" value="{{ old('reply_to_address', $mailConfig?->reply_to_address) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="reply_to_name">Nome reply-to</label>
                        <input class="form-control" id="reply_to_name" name="reply_to_name" value="{{ old('reply_to_name', $mailConfig?->reply_to_name) }}">
                    </div>

                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="verify_peer" name="verify_peer" value="1" @checked(old('verify_peer', $mailConfig?->verify_peer ?? true))>
                            <label class="form-check-label" for="verify_peer">Verificar certificado</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="verify_peer_name" name="verify_peer_name" value="1" @checked(old('verify_peer_name', $mailConfig?->verify_peer_name ?? true))>
                            <label class="form-check-label" for="verify_peer_name">Verificar nome do certificado</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="allow_self_signed" name="allow_self_signed" value="1" @checked(old('allow_self_signed', $mailConfig?->allow_self_signed ?? false))>
                            <label class="form-check-label" for="allow_self_signed">Permitir self-signed</label>
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
                            <label class="form-label" for="to">Enviar teste para</label>
                            <input class="form-control" id="to" name="to" type="email" value="{{ old('to') }}" required>
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
