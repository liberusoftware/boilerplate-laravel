<ul>
    @foreach($menuItems as $menuItem)
        @include('filament-menu-builder::components.plain.menu-item', ['item' => $menuItem])
    @endforeach
</ul>
