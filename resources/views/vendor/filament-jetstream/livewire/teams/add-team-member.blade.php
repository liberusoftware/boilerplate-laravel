<x-filament::section aside>
    <x-slot name="heading">
        {{ __('filament-jetstream::default.add_team_member.section.title') }}
    </x-slot>
    <x-slot name="description">
        {{ __('filament-jetstream::default.add_team_member.section.description') }}
    </x-slot>

    <form wire:submit="addTeamMember">
        {{ $this->form }}
    </form>

    <x-filament-actions::modals />
</x-filament::section>
