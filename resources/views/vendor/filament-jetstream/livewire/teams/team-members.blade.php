<x-filament::section aside>
    <x-slot name="heading">
        {{ __('filament-jetstream::default.team_members.section.title') }}
    </x-slot>
    <x-slot name="description">
        {{ __('filament-jetstream::default.team_members.section.description') }}
    </x-slot>

    {{ $this->table }}

    <x-filament-actions::modals/>

</x-filament::section>
