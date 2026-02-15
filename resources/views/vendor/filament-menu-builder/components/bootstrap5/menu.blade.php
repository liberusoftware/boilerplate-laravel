<ul class="navbar-nav me-auto mb-2 mb-lg-0">
    @foreach($menuItems as $menuItem)
        @include('filament-menu-builder::components.bootstrap5.menu-item', ['item' => $menuItem])
    @endforeach
</ul>
