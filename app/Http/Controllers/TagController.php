<?php

namespace App\Http\Controllers;

use App\Http\Resources\TagResource;

class TagController extends Controller
{
    public function index()
    {
        $this->seo('주제별 보기', '주제별(태그) 목록을 확인할 수 있습니다');

        return view('tag.index');
    }

    public function getTag()
    {
        $tags = \App\Tag::withCount('posts')->get();

        return response()->json(TagResource::collection($tags), 200);
    }
}
