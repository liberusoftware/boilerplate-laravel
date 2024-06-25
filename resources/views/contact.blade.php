{{-- Moved to `resources/views/components/contact-form.blade.php` and renamed to match Laravel component naming convention --}}
@extends('layouts.home')
@section('content')
<div class="container">
    <h1>Contact Us</h1>
    <x-contact-form />
</div>
@endsection
