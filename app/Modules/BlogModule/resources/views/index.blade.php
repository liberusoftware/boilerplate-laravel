@extends('layouts.app')

@section('title', 'Blog Posts')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Blog Posts</h1>
    
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @foreach($posts as $post)
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-3">
                    <a href="{{ route('blog.show', $post['id']) }}" class="text-blue-600 hover:text-blue-800">
                        {{ $post['title'] }}
                    </a>
                </h2>
                <p class="text-gray-600 mb-4">{{ Str::limit($post['content'], 100) }}</p>
                <p class="text-sm text-gray-500">{{ $post['created_at']->format('M d, Y') }}</p>
            </div>
        @endforeach
    </div>
</div>
@endsection