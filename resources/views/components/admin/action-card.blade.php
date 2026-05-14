@props([
    'title',
    'description',
    'href',
    'icon' => 'ri-arrow-right-line',
    'button' => 'Abrir',
])

<div class="card">
    <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div class="d-flex gap-3">
            <span class="admin-stat-icon bg-primary-subtle text-primary flex-shrink-0">
                <i class="{{ $icon }} fs-20"></i>
            </span>
            <div>
                <h5 class="card-title mb-1">{{ $title }}</h5>
                <p class="text-muted mb-0">{{ $description }}</p>
            </div>
        </div>
        <a href="{{ $href }}" class="btn btn-primary flex-shrink-0">{{ $button }}</a>
    </div>
</div>
