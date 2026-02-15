<li class="nav-item {{ $item->wrapper_class }} @if(! $item->children->isEmpty()) dropdown @endif">
    @if($item->children->isEmpty())
        <a
            target="{{ $item->target }}"
            class="nav-link {{ $item->link_class }}"
            href="{{ $item->link }}"
        >
            {{ $item->name }}
        </a>
    @else
        <a
            class="nav-link dropdown-toggle {{ $item->link_class }}"
            href="{{ $item->link }}"
            id="navbarDropdown"
            role="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
        >
            {{ $item->name }}
        </a>
        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
            @foreach($item->children as $child)
                @include('filament-menu-builder::components.bootstrap5.menu-sub-item', ['item' => $child])
            @endforeach
        </ul>
    @endif
</li>
