<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), config('localization.rtl_locales', []), true) ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title', config('app.name'))</title><link rel="canonical" href="{{ url()->current() }}">@themeVite @livewireStyles @stack('head')</head>
<body><a class="skip-link" href="#main-content">{{ __('Skip to content') }}</a><div class="theme-shell"><header role="banner">@yield('header')</header><main id="main-content" tabindex="-1">@yield('content')</main><footer role="contentinfo">@yield('footer')</footer></div>@livewireScripts @stack('scripts')</body>
</html>
