<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
    <meta charset="utf-8" />
    <meta name="application-name" content="{{ config('app.name') }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ config('app.name') }}</title>
    @vite('resources/css/app.css')
    </head>
<body>

            <main>
            </main>

    @vite('resources/js/app.js')
    </body>
</html>
