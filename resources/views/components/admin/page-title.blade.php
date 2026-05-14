@props([
    'title',
    'subtitle' => null,
    'aside' => null,
])

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <div>
                <h4 class="mb-sm-0">{{ $title }}</h4>
                @if ($subtitle)
                    <p class="text-muted mb-0 mt-1">{{ $subtitle }}</p>
                @endif
            </div>
            @if ($aside)
                <div class="page-title-right text-muted">{{ $aside }}</div>
            @endif
        </div>
    </div>
</div>
