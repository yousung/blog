<?php

namespace App\Http\Controllers\Admin;

use App\Events\NaverBlog;
use App\Post;
use App\Http\Controllers\Controller;

class NaverController extends Controller
{
    public function store(Post $post)
    {
        NaverBlog::dispatch('new', $post);
        return response()->json(['data' => '123'], 200, [], JSON_PRETTY_PRINT);
    }
}
