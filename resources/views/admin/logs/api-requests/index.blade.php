@extends('layouts.admin')

@section('title', 'Logs da API')
@section('page-title', 'Logs da API')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Requisições da API</h4>
                <div class="page-title-right text-muted">Dados sensíveis mascarados</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.logs.api-requests.index') }}" class="row g-3 align-items-end mb-4">
                <div class="col-md-2">
                    <label class="form-label" for="date_from">De</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ $filters['date_from'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="date_to">Até</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ $filters['date_to'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="method">Método</label>
                    <input class="form-control" id="method" name="method" value="{{ $filters['method'] ?? '' }}" placeholder="GET">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="status">Status técnico</label>
                    <input class="form-control" id="status" name="status" value="{{ $filters['status'] ?? '' }}" placeholder="SUCCESS">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="search">Busca</label>
                    <input class="form-control" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="request_id, trace_id, tenant, rota ou URI">
                </div>
                <div class="col-md-1">
                    <label class="form-label" for="per_page">Itens</label>
                    <select class="form-select" id="per_page" name="per_page">
                        @foreach ([15, 30, 50] as $perPage)
                            <option value="{{ $perPage }}" @selected((int) $filters['per_page'] === $perPage)>{{ $perPage }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <button class="btn btn-primary w-100" type="submit">Filtrar</button>
                </div>
            </form>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <small class="text-muted">Período máximo: {{ $maxPeriodDays }} dias.</small>
                <small class="text-muted">{{ $logs->firstItem() ?? 0 }}-{{ $logs->lastItem() ?? 0 }} de {{ $logs->total() }}</small>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Quando</th>
                            <th>Método</th>
                            <th>HTTP</th>
                            <th>Status</th>
                            <th>Tenant</th>
                            <th>Rota</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td>{{ $log['id'] }}</td>
                                <td>{{ $log['created_at'] }}</td>
                                <td><span class="badge bg-secondary">{{ $log['method'] }}</span></td>
                                <td>{{ $log['http_status'] }}</td>
                                <td>{{ $log['processing_status'] }}</td>
                                <td>{{ $log['tenant_code'] ?? '-' }}</td>
                                <td class="text-truncate" style="max-width: 360px;">{{ $log['route'] ?? $log['uri'] }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.logs.api-requests.show', $log['id']) }}">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Nenhum log encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
@endsection
