<x-filament::section aside>
    <x-slot name="heading">
        {{ __('filament-jetstream::default.delete_team.section.title') }}
    </x-slot>
    <x-slot name="description">
        {{ __('filament-jetstream::default.delete_team.section.description') }}
    </x-slot>

    <form wire:submit="deleteTeam">
        {{ $this->form }}
    </form>

    <x-filament-actions::modals />
</x-filament::section>
