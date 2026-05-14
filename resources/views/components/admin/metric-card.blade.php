@props([
    'label',
    'value',
    'icon' => 'ri-pulse-line',
    'tone' => 'primary',
    'meta' => null,
])

<div class="card card-animate">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <div class="flex-grow-1 overflow-hidden">
                <p class="text-uppercase fw-medium text-muted text-truncate mb-0">{{ $label }}</p>
            </div>
            <div class="flex-shrink-0">
                <span class="admin-stat-icon bg-{{ $tone }}-subtle text-{{ $tone }}">
                    <i class="{{ $icon }} fs-20"></i>
                </span>
            </div>
        </div>
        <div class="d-flex align-items-end justify-content-between mt-4">
            <div>
                <h4 class="fs-22 fw-semibold ff-secondary mb-1">{{ $value }}</h4>
                @if ($meta)
                    <span class="text-muted">{{ $meta }}</span>
                @endif
            </div>
        </div>
    </div>
</div>
