@extends('layouts.admin')

@section('title', 'Detalhe do log da API')
@section('page-title', 'Detalhe do log da API')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Log #{{ $log['id'] }}</h4>
                <div class="d-flex gap-2">
                    @if ($canViewPayload)
                        <a href="{{ route('admin.logs.api-requests.payload', $log['id']) }}" class="btn btn-primary btn-sm">Ver payload detalhado</a>
                    @endif
                    <a href="{{ route('admin.logs.api-requests.index') }}" class="btn btn-outline-secondary btn-sm">Voltar</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><strong>Método:</strong> {{ $log['method'] }}</div>
                <div class="col-md-3"><strong>HTTP:</strong> {{ $log['http_status'] }}</div>
                <div class="col-md-3"><strong>Status:</strong> {{ $log['processing_status'] }}</div>
                <div class="col-md-3"><strong>Duração:</strong> {{ $log['duration_ms'] }} ms</div>
                <div class="col-md-6"><strong>Request ID:</strong> {{ $log['request_id'] }}</div>
                <div class="col-md-6"><strong>Trace ID:</strong> {{ $log['trace_id'] }}</div>
                <div class="col-md-6"><strong>Tenant:</strong> {{ $log['tenant_code'] ?? '-' }}</div>
                <div class="col-md-6"><strong>IP:</strong> {{ $log['ip'] ?? '-' }}</div>
                <div class="col-12"><strong>URI:</strong> {{ $log['uri'] }}</div>
                <div class="col-12"><strong>User-Agent:</strong> {{ $log['user_agent'] ?? '-' }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body d-sm-flex align-items-center justify-content-between">
            <div>
                <h5 class="card-title mb-1">Payload</h5>
                <p class="text-muted mb-0">Visualização detalhada restrita e auditada.</p>
            </div>
            @if ($canViewPayload)
                <a href="{{ route('admin.logs.api-requests.payload', $log['id']) }}" class="btn btn-primary btn-sm mt-3 mt-sm-0">Ver payload detalhado</a>
            @else
                <span class="badge bg-warning-subtle text-warning mt-3 mt-sm-0">Permissão extra necessária</span>
            @endif
        </div>
    </div>

    @if ($log['message'])
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Mensagem</h5>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $log['message'] }}</p>
            </div>
        </div>
    @endif
@endsection
