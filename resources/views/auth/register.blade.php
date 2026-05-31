@extends('layouts.guest')

@section('content')
    <div class="min-h-full flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4">

        <div class="w-full sm:max-w-md mt-6 px-4 sm:px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <x-validation-errors class="mb-4" />

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div>
                    <x-label for="name" value="{{ __('Name') }}" />
                    <x-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                </div>

                <div class="mt-4">
                    <x-label for="email" value="{{ __('Email') }}" />
                    <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                </div>

                <div class="mt-4">
                    <x-label for="password" value="{{ __('Password') }}" />
                    <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                </div>

                <div class="mt-4">
                    <x-label for="password_confirmation" value="{{ __('Confirm Password') }}" />
                    <x-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                </div>

                <div class="mt-4">
                    <x-label for="role" value="{{ __('Role') }}" />
                    <select id="role" name="role"
                        class="block mt-1 w-full border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm"
                        required>
                        <option value="">{{ __('Select a role') }}</option>
                        <option value="tenant">{{ __('Tenant') }}</option>
                        <option value="buyer">{{ __('Buyer') }}</option>
                        <option value="seller">{{ __('Seller') }}</option>
                        <option value="landlord">{{ __('Landlord') }}</option>
                        <option value="contractor">{{ __('Contractor') }}</option>
                    </select>
                </div>

                @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                    <div class="mt-4">
                        <x-label for="terms">
                            <div class="flex items-center">
                                <input type="checkbox" id="terms" name="terms" required
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <div class="ml-2 text-sm">
                                    {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                        'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="underline text-gray-600 hover:text-gray-900">'.__('Terms of Service').'</a>',
                                        'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="underline text-gray-600 hover:text-gray-900">'.__('Privacy Policy').'</a>',
                                    ]) !!}
                                </div>
                            </div>
                        </x-label>
                    </div>
                @endif

                <div class="flex items-center justify-end mt-4">
                    <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('login') }}">
                        {{ __('Already registered?') }}
                    </a>

                    <x-button class="inline-flex items-center px-4 py-2 bg-green-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150 ml-4">
                        {{ __('Register') }}
                    </x-button>
                </div>
            </form>

            @if (JoelButcher\Socialstream\Socialstream::show())
                <x-socialstream::socialstream />
            @endif
        </div>
    </div>
@endsection
