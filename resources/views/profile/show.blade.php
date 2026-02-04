<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                @livewire(\Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm::class)

                <x-section-border />
            @endif

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()) && ! is_null($user->getAuthPassword()))
                <div class="mt-10 sm:mt-0">
                    @livewire(\Laravel\Jetstream\Http\Livewire\UpdatePasswordForm::class)
                </div>

                <x-section-border />
            @else
                <div class="mt-10 sm:mt-0">
                    @livewire(\Laravel\Jetstream\Http\Livewire\SetPasswordForm::class)
                </div>

                <x-section-border />
            @endif

            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication() && ! is_null($user->getAuthPassword()))
                <div class="mt-10 sm:mt-0">
                    @livewire(\Laravel\Jetstream\Http\Livewire\TwoFactorAuthenticationForm::class)
                </div>

                <x-section-border />
            @endif

            @if (JoelButcher\Socialstream\Socialstream::show())
                <div class="mt-10 sm:mt-0">
                    @livewire(\Laravel\Jetstream\Http\Livewire\ConnectedAccountsForm::class)
                </div>
            @endif


            @if ( ! is_null($user->getAuthPassword()))
                <x-section-border />

                <div class="mt-10 sm:mt-0">
                    @livewire(\Laravel\Jetstream\Http\Livewire\LogoutOtherBrowserSessionsForm::class)
                </div>
            @endif

            @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures() && ! is_null($user->getAuthPassword()))
                <x-section-border />

                <div class="mt-10 sm:mt-0">
                    @livewire(\Laravel\Jetstream\Http\Livewire\DeleteUserForm::class)
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
