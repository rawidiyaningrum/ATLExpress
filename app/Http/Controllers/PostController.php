<?php

namespace App\Http\Controllers;

use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::published()->latest('published_at')->paginate(9);

        return view('posts.index', compact('posts'));
    }

    public function show($slug)
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();

        return view('posts.show', compact('post'));
    }
}
