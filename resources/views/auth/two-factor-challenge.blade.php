@extends('layouts.guest')

@section('content')
    <div x-data="{ recovery: false }">
        <x-auth.card title="{{ __('Two-factor authentication') }}">
            <p class="-mt-2 mb-5 text-sm leading-relaxed text-ink-muted-dark text-pretty" x-show="! recovery">
                {{ __('Confirm access to your account by entering the authentication code from your authenticator app.') }}
            </p>
            <p class="-mt-2 mb-5 text-sm leading-relaxed text-ink-muted-dark text-pretty" x-cloak x-show="recovery">
                {{ __('Confirm access to your account by entering one of your emergency recovery codes.') }}
            </p>

            <x-validation-errors class="mb-4" />

            <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-4">
                @csrf

                <div x-show="! recovery">
                    <x-auth.label for="code" value="{{ __('Code') }}" />
                    <x-auth.input id="code" type="text" inputmode="numeric" name="code" autofocus x-ref="code"
                        autocomplete="one-time-code" placeholder="123456" />
                </div>

                <div x-cloak x-show="recovery">
                    <x-auth.label for="recovery_code" value="{{ __('Recovery Code') }}" />
                    <x-auth.input id="recovery_code" type="text" name="recovery_code" x-ref="recovery_code"
                        autocomplete="one-time-code" />
                </div>

                <x-auth.button>{{ __('Log in') }}</x-auth.button>
            </form>

            <x-slot name="footer">
                <button type="button" class="font-medium text-teal-signal underline-offset-4 transition hover:underline focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-signal"
                    x-show="! recovery"
                    x-on:click="recovery = true; $nextTick(() => { $refs.recovery_code.focus() })">
                    {{ __('Use a recovery code') }}
                </button>

                <button type="button" class="font-medium text-teal-signal underline-offset-4 transition hover:underline focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-signal"
                    x-cloak x-show="recovery"
                    x-on:click="recovery = false; $nextTick(() => { $refs.code.focus() })">
                    {{ __('Use an authentication code') }}
                </button>
            </x-slot>
        </x-auth.card>
    </div>
@endsection
