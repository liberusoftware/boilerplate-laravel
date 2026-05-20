<x-filament::section aside>
    <x-slot name="heading">
        {{ __('filament-jetstream::default.update_team_name.section.title') }}
    </x-slot>
    <x-slot name="description">
        {{ __('filament-jetstream::default.update_team_name.section.description') }}
    </x-slot>

    <form wire:submit="updateTeamName">
        {{ $this->form }}
    </form>
    <x-filament-actions::modals />

</x-filament::section>
