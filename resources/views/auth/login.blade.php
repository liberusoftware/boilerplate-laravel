@extends('layouts.guest')

@section('content')
    <div class="min-h-full max-w-xl w-md flex flex-col sm:justify-center items-center pt-15 sm:pt-5 my-20">
        <div class="w-full mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <div class="mb-4 text-sm text-gray-600">
                {{ __('Please sign in to access the admin panel.') }}
            </div>

            <x-validation-errors class="mb-4" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div>
                    <x-label for="email" value="{{ __('Email') }}" />
                    <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')"
                        placeholder="Enter your email" required />
                </div>

                <div class="mt-4">
                    <x-label for="password" value="{{ __('Password') }}" />
                    <x-input id="password" class="block mt-1 w-full" type="password" name="password" required
                        autocomplete="current-password" placeholder="Enter your password" />
                </div>

                <div class="mt-4">
                    <label for="remember_me" class="flex items-center text-sm text-gray-700">
                        <input type="checkbox" id="remember_me" name="remember"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-0 transition duration-150" />
                        <span class="ml-2"> {{ __('Remember me') }} </span>
                    </label>
                </div>


                <div class="flex items-center justify-end mt-4">
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-green-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150 ml-4">
                        {{ __('Log in') }}
                    </button>
                </div>

                <a href="/forgot-password" class="underline text-sm text-gray-600 hover:text-gray-900">Forgot password?</a>
            </form>
        </div>
    </div>
@endsection
