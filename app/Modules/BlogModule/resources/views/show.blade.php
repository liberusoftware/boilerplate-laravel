@extends('layouts.app')

@section('title', $post['title'])

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-4xl font-bold mb-4">{{ $post['title'] }}</h1>
        <p class="text-gray-600 mb-8">Published on {{ $post['created_at']->format('F d, Y') }}</p>
        
        <div class="prose max-w-none">
            <p>{{ $post['content'] }}</p>
        </div>
        
        <div class="mt-8">
            <a href="{{ route('blog.index') }}" class="text-blue-600 hover:text-blue-800">
                ← Back to Blog
            </a>
        </div>
    </div>
</div>
@endsection