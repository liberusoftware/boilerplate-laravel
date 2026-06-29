@extends('layouts.guest')

@section('content')
    <x-auth.card title="{{ __('Choose a new password') }}"
        subtitle="{{ __('Set a new password for your account.') }}">
        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <x-auth.label for="email" value="{{ __('Email') }}" />
                <x-auth.input id="email" type="email" name="email" :value="old('email', $request->email)"
                    required autofocus autocomplete="username" />
            </div>

            <div>
                <x-auth.label for="password" value="{{ __('Password') }}" />
                <x-auth.input id="password" type="password" name="password" required
                    autocomplete="new-password" placeholder="••••••••" />
            </div>

            <div>
                <x-auth.label for="password_confirmation" value="{{ __('Confirm Password') }}" />
                <x-auth.input id="password_confirmation" type="password" name="password_confirmation" required
                    autocomplete="new-password" placeholder="••••••••" />
            </div>

            <x-auth.button>{{ __('Reset password') }}</x-auth.button>
        </form>
    </x-auth.card>
@endsection
