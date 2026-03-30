<div>
    <form wire:submit="save" x-data="{
        data: $wire.entangle('data'),
        sortables: [],
        getDataStructure(parentNode) {
          const items = Array.from(parentNode.children).filter((item) => {
            return item.classList.contains('item');
          }); // Get children items of the current node

          return Array.from(items).map((item) => {
            const id = item.getAttribute('data-id');
            const nestedContainer = item.querySelector('.nested');
            const children = nestedContainer ? this.getDataStructure(nestedContainer): [];

            return { id: parseInt(id), children };
          });
        }
    }"
    x-on:menu-item-created.window="() => {
        console.log('menu-item-created');
    }"
    >
        @if($items->count() > 0)
        <div class="nested-wrapper">
            <div id="parentNested" class="nested"
                 x-data="{
                    init(){
                        new Sortable(this.$el, {
                            handle: '.handle',
                            group: 'nested',
                            animation: 150,
                            fallbackOnBody: true,
                            swapThreshold: 0.65,
                            onEnd: (evt) => {
                                this.data = getDataStructure(document.getElementById('parentNested'));
                            }
                        })
                    },
                }">
                @foreach($items as $item)
                    @include('filament-menu-builder::livewire.menu-item', ['item' => $item])
                @endforeach
            </div>
        </div>
        <x-filament::button
            :dark-mode="config('filament.dark_mode')"
            wire:loading.attr="disabled"
            type="submit"
            class="mt-2"
        >
            <x-filament::loading-indicator wire:loading class="h-5 w-5" />
            {{ __('filament-menu-builder::menu-builder.save') }}
        </x-filament::button>

        <x-filament::button
            :dark-mode="config('filament.dark_mode')"
            wire:loading.attr="disabled"
            type="button"
            class="mt-2"
            color="danger"
            wire:click="$refresh"
        >
            <x-filament::loading-indicator wire:loading class="h-5 w-5" />
            {{ __('filament-menu-builder::menu-builder.reset') }}
        </x-filament::button>
        <p class="text-gray-500 text-center mt-2 text-[13px]">
            {{ __('filament-menu-builder::menu-builder.menu_item_information') }}
        </p>
        @else
            <div class="text-gray-500 text-center">
                <p>
                    {{ __('filament-menu-builder::menu-builder.empty_menu_items_hint_1') }}
                </p>
                <p>
                    {{ __('filament-menu-builder::menu-builder.empty_menu_items_hint_2') }}
                </p>
            </div>
        @endif
    </form>

    <x-filament-actions::modals />
</div>
