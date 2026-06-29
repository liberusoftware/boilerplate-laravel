@extends('layouts.guest')

@section('content')
    <x-auth.card title="{{ __('Confirm your password') }}"
        subtitle="{{ __('This is a secure area. Please confirm your password before continuing.') }}">
        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
            @csrf

            <div>
                <x-auth.label for="password" value="{{ __('Password') }}" />
                <x-auth.input id="password" type="password" name="password" required
                    autocomplete="current-password" autofocus placeholder="••••••••" />
            </div>

            <x-auth.button>{{ __('Confirm') }}</x-auth.button>
        </form>
    </x-auth.card>
@endsection
