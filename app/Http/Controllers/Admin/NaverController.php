<?php

namespace App\Http\Controllers\Admin;

use App\Events\NaverBlog;
use App\Post;
use App\Http\Controllers\Controller;

class NaverController extends Controller
{
    public function store(Post $post)
    {
        $type = $post->naver ? 'edit' : 'new';
        NaverBlog::dispatch($type, $post);

        return response()->json(['data' => $post], 200, [], JSON_PRETTY_PRINT);
    }
}
