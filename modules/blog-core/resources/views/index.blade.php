<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>Blog — {{ config('app.name') }}</title>
</head>
<body>
    <main>
        <h1>Blog</h1>
        @forelse ($posts as $post)
            <article>
                <h2>{{ $post->title }}</h2>
                <p>{{ $post->body }}</p>
            </article>
        @empty
            <p>No posts yet.</p>
        @endforelse
        {{ $posts->links() }}
    </main>
</body>
</html>
