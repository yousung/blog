<?php

namespace App\Http\Controllers;

use App\Post;

class PageController extends Controller implements Cacheble
{
    public function cacheTags()
    {
        return 'post';
    }

    public function search()
    {
        $this->seo('검색', '제목, 부제목, 내용, 주제(태그), 시리즈명으로 검색이 가능합니다');

        return view('search');
    }

    public function index()
    {
        $this->seo('감성개발자', '코드에서 감성이 묻어나오는 개발자 러비쥬입니다');

        $query = Post::with(['user', 'tags', 'series'])->latest();

        $posts = $this->cache(cache_key('home.index'), $query, 'simplePaginate', 15);


        return view('index', compact('posts', 'css'));
    }
}
