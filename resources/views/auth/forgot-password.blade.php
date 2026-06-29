@extends('layouts.guest')

@section('content')
    <x-auth.card title="{{ __('Reset your password') }}"
        subtitle="{{ __('Enter your email and we will send you a link to choose a new password.') }}">
        @session('status')
            <div class="mb-4 rounded-lg border border-teal-signal/30 bg-teal-signal/10 px-3 py-2 text-sm font-medium text-teal-signal">
                {{ $value }}
            </div>
        @endsession

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

            <div>
                <x-auth.label for="email" value="{{ __('Email') }}" />
                <x-auth.input id="email" type="email" name="email" :value="old('email')"
                    placeholder="you@example.com" required autofocus autocomplete="username" />
            </div>

            <x-auth.button>{{ __('Email password reset link') }}</x-auth.button>
        </form>

        <x-slot name="footer">
            <a href="{{ route('login') }}"
                class="font-semibold text-teal-signal underline-offset-4 transition hover:underline focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-signal">
                {{ __('Back to sign in') }}
            </a>
        </x-slot>
    </x-auth.card>
@endsection
