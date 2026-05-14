@extends('layouts.admin')

@section('title', 'Dashboard administrativo')
@section('page-title', 'Dashboard administrativo')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Visão operacional</h4>
                <div class="page-title-right text-muted">Sessão web administrativa</div>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach ($metrics as $label => $value)
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate">
                    <div class="card-body">
                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">{{ str_replace('_', ' ', $label) }}</p>
                        <div class="d-flex align-items-end justify-content-between mt-4">
                            <h4 class="fs-22 fw-semibold ff-secondary mb-0">{{ number_format((int) $value, 0, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <h5 class="card-title mb-1">Logs da API</h5>
                        <p class="text-muted mb-0">Consulta administrativa com mascaramento aplicado na visualização.</p>
                    </div>
                    <a href="{{ route('admin.logs.api-requests.index') }}" class="btn btn-primary">Abrir logs</a>
                </div>
            </div>
        </div>
    </div>
@endsection
