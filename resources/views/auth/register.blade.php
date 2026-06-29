@extends('layouts.guest')

@section('content')
    <x-auth.card title="{{ __('Create your account') }}" subtitle="{{ __('Get started in less than a minute.') }}">
        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <x-auth.label for="name" value="{{ __('Name') }}" />
                <x-auth.input id="name" type="text" name="name" :value="old('name')"
                    placeholder="{{ __('Your name') }}" required autofocus autocomplete="name" />
            </div>

            <div>
                <x-auth.label for="email" value="{{ __('Email') }}" />
                <x-auth.input id="email" type="email" name="email" :value="old('email')"
                    placeholder="you@example.com" required autocomplete="username" />
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

            <div>
                <x-auth.label for="role" value="{{ __('Role') }}" />
                <select id="role" name="role" required
                    class="block w-full rounded-lg border border-border-dark bg-canvas px-3.5 py-2.5 text-sm text-ink-inverse transition focus:border-teal-signal focus:outline-none focus:ring-2 focus:ring-teal-signal/40">
                    <option value="">{{ __('Select a role') }}</option>
                    <option value="tenant">{{ __('Tenant') }}</option>
                    <option value="buyer">{{ __('Buyer') }}</option>
                    <option value="seller">{{ __('Seller') }}</option>
                    <option value="landlord">{{ __('Landlord') }}</option>
                    <option value="contractor">{{ __('Contractor') }}</option>
                </select>
            </div>

            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                <label for="terms" class="flex items-start gap-2 text-sm text-ink-muted-dark">
                    <input type="checkbox" id="terms" name="terms" required
                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-border-dark bg-canvas accent-teal-signal" />
                    <span>
                        {!! __('I agree to the :terms_of_service and :privacy_policy', [
                            'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="font-medium text-teal-signal underline-offset-4 hover:underline">'.__('Terms of Service').'</a>',
                            'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="font-medium text-teal-signal underline-offset-4 hover:underline">'.__('Privacy Policy').'</a>',
                        ]) !!}
                    </span>
                </label>
            @endif

            <x-auth.button>{{ __('Create account') }}</x-auth.button>
        </form>

        @if (JoelButcher\Socialstream\Socialstream::show())
            <div class="mt-6">
                <x-socialstream::socialstream :label="__('Or sign up with')" />
            </div>
        @endif

        <x-slot name="footer">
            {{ __('Already have an account?') }}
            <a href="{{ route('login') }}"
                class="font-semibold text-teal-signal underline-offset-4 transition hover:underline focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-signal">
                {{ __('Sign in') }}
            </a>
        </x-slot>
    </x-auth.card>
@endsection
