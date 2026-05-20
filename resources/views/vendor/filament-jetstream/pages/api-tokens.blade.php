@php use Filament\Jetstream\Jetstream; @endphp
<x-filament-panels::page>
    @if (Jetstream::plugin()?->hasApiTokensFeatures())
        @livewire(Filament\Jetstream\Livewire\ApiTokens\CreateApiToken::class)

        @livewire(Filament\Jetstream\Livewire\ApiTokens\ManageApiTokens::class)
    @endif
</x-filament-panels::page>
