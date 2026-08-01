<?php

namespace Liberu\Blog\Core\Http\Controllers;

use Illuminate\Contracts\View\View;
use Liberu\Blog\Core\Models\Post;

class BlogController
{
    public function index(): View
    {
        $perPage = config('blog.posts_per_page', 10);

        $posts = Post::where('status', 'published')
            ->latest()
            ->paginate(is_int($perPage) ? $perPage : 10);

        return view('blog::index', ['posts' => $posts]);
    }
}
