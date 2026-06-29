@extends('layouts.guest')

@section('content')
    <x-auth.card title="{{ __('Verify your email') }}"
        subtitle="{{ __('Before continuing, please verify your email by clicking the link we just sent. Didn\'t get it? We can send another.') }}">
        @if (session('status') == 'verification-link-sent')
            <div class="mb-4 rounded-lg border border-teal-signal/30 bg-teal-signal/10 px-3 py-2 text-sm font-medium text-teal-signal">
                {{ __('A new verification link has been sent to your email address.') }}
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-auth.button>{{ __('Resend verification email') }}</x-auth.button>
        </form>

        <x-slot name="footer">
            <div class="flex items-center justify-center gap-4">
                <a href="{{ route('profile.show') }}"
                    class="font-medium text-ink-muted-dark underline-offset-4 transition hover:text-ink-inverse hover:underline focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-signal">
                    {{ __('Edit profile') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="font-medium text-ink-muted-dark underline-offset-4 transition hover:text-ink-inverse hover:underline focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-signal">
                        {{ __('Log out') }}
                    </button>
                </form>
            </div>
        </x-slot>
    </x-auth.card>
@endsection
