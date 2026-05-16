@foreach ($items as $item)
    @php
        $children = $item['children'] ?? [];
        $hasChildren = count($children) > 0;
        $isActive = ($item['active'] ?? false) === true;
        $collapseId = 'sidebar-menu-' . \Illuminate\Support\Str::slug((string) ($item['code'] ?? uniqid('item-', true)));
        $target = ($item['opens_in_new_tab'] ?? false) === true ? '_blank' : null;
    @endphp

    <li class="nav-item">
        @if ($hasChildren)
            <a class="nav-link menu-link {{ $isActive ? 'active' : '' }}" href="#{{ $collapseId }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ $isActive ? 'true' : 'false' }}" aria-controls="{{ $collapseId }}">
                @if (! empty($item['icon']))
                    <i class="{{ $item['icon'] }}"></i>
                @endif
                <span>{{ $item['title'] }}</span>
            </a>
            <div class="collapse menu-dropdown {{ $isActive ? 'show' : '' }}" id="{{ $collapseId }}">
                <ul class="nav nav-sm flex-column">
                    @include('admin.partials.sidebar-menu-items', ['items' => $children])
                </ul>
            </div>
        @elseif (! empty($item['url']))
            <a class="nav-link menu-link {{ $isActive ? 'active' : '' }}" href="{{ $item['url'] }}" @if ($target !== null) target="{{ $target }}" rel="noopener noreferrer" @endif>
                @if (! empty($item['icon']))
                    <i class="{{ $item['icon'] }}"></i>
                @endif
                <span>{{ $item['title'] }}</span>
            </a>
        @endif
    </li>
@endforeach
