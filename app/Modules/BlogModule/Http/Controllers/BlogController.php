<?php

namespace App\Modules\BlogModule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\BlogModule\Services\BlogService;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    protected BlogService $blogService;

    public function __construct(BlogService $blogService)
    {
        $this->blogService = $blogService;
    }

    /**
     * Display a listing of blog posts.
     */
    public function index()
    {
        $posts = $this->blogService->getAllPosts();
        return view('blog::index', compact('posts'));
    }

    /**
     * Display the specified blog post.
     */
    public function show(int $id)
    {
        $post = $this->blogService->getPost($id);
        
        if (!$post) {
            abort(404);
        }

        return view('blog::show', compact('post'));
    }

    /**
     * Store a newly created blog post.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $post = $this->blogService->createPost($request->only(['title', 'content']));

        return response()->json($post, 201);
    }
}