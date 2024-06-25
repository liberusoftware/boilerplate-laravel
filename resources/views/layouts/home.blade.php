@extends('layouts.app')
@include('components.home-navbar')
@include('components.home-header')

<main>
        @yield('content')
</main>
@include('components.footer')
