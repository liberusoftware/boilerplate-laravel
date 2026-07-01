<?php

namespace App\Modules\Blog\Http\Controllers;

use App\Modules\Blog\Models\Post;
use Illuminate\Contracts\View\View;

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
