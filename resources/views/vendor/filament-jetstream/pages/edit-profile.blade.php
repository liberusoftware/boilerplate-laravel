@use('Filament\Jetstream\Jetstream')

<x-filament-panels::page>
    @if (Jetstream::plugin()?->enabledProfileInformationUpdate())
        @livewire(Filament\Jetstream\Livewire\Profile\UpdateProfileInformation::class)
    @endif

    @if (Jetstream::plugin()?->enabledPasswordUpdate())
        @livewire(Filament\Jetstream\Livewire\Profile\UpdatePassword::class)
    @endif

    @if (Jetstream::plugin()?->enabledTwoFactorAuthetication())
        @livewire(\Stephenjude\FilamentTwoFactorAuthentication\Livewire\TwoFactorAuthentication::class)
    @endif

    @if (Jetstream::plugin()?->enabledPasskeyAuthetication())
        @livewire(\Stephenjude\FilamentTwoFactorAuthentication\Livewire\PasskeyAuthentication::class)
    @endif

    @if (Jetstream::plugin()?->enabledLogoutOtherBrowserSessions())
        @livewire(Filament\Jetstream\Livewire\Profile\LogoutOtherBrowserSessions::class)
    @endif

    @if (Jetstream::plugin()?->enabledDeleteAccount())
        @livewire(Filament\Jetstream\Livewire\Profile\DeleteAccount::class)
    @endif
</x-filament-panels::page>
