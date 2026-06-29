@extends('layouts.guest')

@section('content')
    <x-auth.card title="{{ __('Welcome back') }}" subtitle="{{ __('Sign in to access your dashboard.') }}">
        <x-validation-errors class="mb-4" />

        @if (session('status'))
            <div class="mb-4 rounded-lg border border-teal-signal/30 bg-teal-signal/10 px-3 py-2 text-sm font-medium text-teal-signal">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <x-auth.label for="email" value="{{ __('Email') }}" />
                <x-auth.input id="email" type="email" name="email" :value="old('email')"
                    placeholder="you@example.com" required autofocus autocomplete="username" />
            </div>

            <div>
                <x-auth.label for="password" value="{{ __('Password') }}" />
                <x-auth.input id="password" type="password" name="password" required
                    autocomplete="current-password" placeholder="••••••••" />
            </div>

            <div class="flex items-center justify-between">
                <label for="remember_me" class="flex items-center gap-2 text-sm text-ink-muted-dark">
                    <input type="checkbox" id="remember_me" name="remember"
                        class="h-4 w-4 rounded border-border-dark bg-canvas accent-teal-signal" />
                    {{ __('Remember me') }}
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                        class="text-sm text-ink-muted-dark underline-offset-4 transition hover:text-ink-inverse hover:underline focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-signal">
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </div>

            <x-auth.button>{{ __('Log in') }}</x-auth.button>
        </form>

        @if (JoelButcher\Socialstream\Socialstream::show())
            <div class="mt-6">
                <x-socialstream::socialstream />
            </div>
        @endif

        @if (Route::has('register'))
            <x-slot name="footer">
                {{ __("Don't have an account?") }}
                <a href="{{ route('register') }}"
                    class="font-semibold text-teal-signal underline-offset-4 transition hover:underline focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-signal">
                    {{ __('Create one') }}
                </a>
            </x-slot>
        @endif
    </x-auth.card>
@endsection
