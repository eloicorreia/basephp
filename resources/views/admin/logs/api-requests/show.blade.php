@extends('layouts.admin')

@section('title', 'Detalhe do log da API')
@section('page-title', 'Detalhe do log da API')

@section('content')
    @php
        $json = static fn (mixed $value): string => (string) json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    @endphp

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Log #{{ $log['id'] }}</h4>
                <a href="{{ route('admin.logs.api-requests.index') }}" class="btn btn-outline-secondary btn-sm">Voltar</a>
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
            </div>
        </div>
    </div>

    @foreach ([
        'Headers da requisição' => $log['request_headers'],
        'Query da requisição' => $log['request_query'],
        'Body da requisição' => $log['request_body'],
        'Body da resposta' => $log['response_body'],
    ] as $title => $payload)
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ $title }}</h5>
            </div>
            <div class="card-body">
                <pre class="mb-0 p-3 bg-light rounded small">{{ $json($payload) }}</pre>
            </div>
        </div>
    @endforeach

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
