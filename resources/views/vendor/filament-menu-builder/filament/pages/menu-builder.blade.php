<x-filament-panels::page>
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-4">
            @livewire('menu-item-form', ['menuId' => $this->record->id])
        </div>
        <div class="col-span-8">
            @livewire('menu-builder', ['menuId' => $this->record->id])
        </div>
    </div>
</x-filament-panels::page>

