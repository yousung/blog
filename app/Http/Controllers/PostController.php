<?php

namespace App\Http\Controllers;

use App\Post;
use App\filters\PostFilter;

class PostController extends Controller implements Cacheble
{
    public function cacheTags()
    {
        return 'post';
    }

    public function __construct()
    {
        parent::__construct();
        $this->middleware('obfuscate')->only('show');
    }

    public function index(PostFilter $filter)
    {
        if ($search = \Request::input('search')) {
            $title = "'{$search}' 검색결과";
            $desc = "'{$search}' 로 포스트 제목, 부제목, 태그, 시리즈를 검색합니다";
        } elseif ($tag = \Request::input('tag')) {
            $title = "'{$tag}' 검색결과";
            $desc = "'{$tag}' 로 태그 검색합니다";
        } elseif ($series = \Request::input('series')) {
            $title ="'{$series}' 검색결과";
            $desc = "'{$series}' 리스트 입니다";
        } else {
            $title ='포스트 전체보기';
            $desc = '작성된 모든 포스트를 확인할 수 있습니다.';
        }

        $query = Post::Filter($filter);
        $this->seo($title, $desc);

        $posts = $this->cache(cache_key(), $query, 'simplePaginate', 15);
        $this->postList($posts);

        return view('post.index', compact('posts'));
    }

    public function show($post)
    {
        $this->seo($post->title, $post->subTitle, $post);
        $this->schema($post);

        $series = optional($post->series)->posts ?? [];

        $this->hitUp($post);

        return view('post.show', compact('post', 'series'));
    }
}
