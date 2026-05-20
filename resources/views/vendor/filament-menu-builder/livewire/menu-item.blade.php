<div class="item" data-id="{{ $item->id }}" wire:key="{{'menu-item-'.$item->id}}">
    <div @class([
        'flex justify-between mb-2 content-center rounded bg-white border border-gray-300 shadow-sm pr-2 dark:bg-gray-900 dark:border-gray-800' => true
])>
        <div class="flex content-center items-center">
            <div class="border-r-2 border-gray-300 dark:border-gray-800 cursor-pointer bg-grey-400">
                <x-heroicon-o-arrows-up-down class="w-6 h-6 m-2 handle" />
            </div>
            <div class="ml-2 flex">
                <span class="font-medium">{{ $item->menu_name }}</span>
                <x-filament::badge size="xs" class="ml-2 px-2" color="gray">
                    {{ $item->normalized_type }}
                </x-filament::badge>
                @if(! $item->is_link_resolved)
                <x-filament::badge size="xs" class="ml-2 px-2" color="danger">
                    {{ $item->link_error ?? 'Link cannot be resolved!' }}
                </x-filament::badge>
                @endif
            </div>
        </div>
        <div class="flex gap-2 items-center [&_svg]:shrink-0">
            {{($this->createSubItemAction)(['menuItemId' => $item->id])}}
            {{($this->editAction)(['menuItemId' => $item->id])}}
            {{($this->duplicateAction)(['menuItemId' => $item->id])}}
            {{($this->deleteAction)(['menuItemId' => $item->id])}}
            <x-filament-actions::group class="hidden" :actions="[
                ($this->viewAction)(['menuItemId' => $item->id]),
                ($this->goToLinkAction)([])->url($item->is_link_resolved ? $item->link : '#'),
            ]" />
        </div>
    </div>

    <div
        @class(['nested ml-6' => true])
        data-id="{{ $item->id }}"
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
        }"
    >
        @foreach($item->children as $children)
            @include('filament-menu-builder::livewire.menu-item', ['item' => $children])
        @endforeach
    </div>
</div>
