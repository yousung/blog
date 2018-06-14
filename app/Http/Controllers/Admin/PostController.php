<?php

namespace App\Http\Controllers\Admin;

use App\Events\DelTagsChange;
use App\Events\EmailSender;
use App\Events\ModelChange;
use App\Events\NaverBlog;
use App\filters\PostFilter;
use App\Http\Controllers\Cacheble;
use App\Http\Requests\PostRequest;
use App\Post;
use App\Http\Controllers\Controller;

class PostController extends Controller implements Cacheble
{
    public function cacheTags()
    {
        return ['post', 'series'];
    }

    public function index(PostFilter $filter)
    {
        $query = Post::Filter($filter);

        $posts = $this->cache(cache_key('admin.post.index'), $query, 'paginate', 15);

        return view('admin.post.index', compact('posts'));
    }

    public function show(Post $post)
    {
        return view('admin.post.show', compact('post'));
    }

    public function create()
    {
        $post = new Post();

        return view('admin.post.create', compact('post'));
    }

    public function store(PostRequest $request)
    {
        $post = Post::create($request->getData());

        $this->common('작성', $request, $post);
        EmailSender::dispatch($post);

        return redirect(route('admin.post.show', $post->id));
    }

    public function edit(Post $post)
    {
        return view('admin.post.edit', compact('post'));
    }

    public function update(PostRequest $request, Post $post)
    {
        $post->update($request->getData());
        NaverBlog::dispatch('edit', $post);

        $this->common('수정', $request, $post);

        return redirect(route('admin.post.show', $post->id).query_string('page', 'search'));
    }

    public function destroy(Post $post)
    {
        NaverBlog::dispatch('del', $post);
        $post->delete();
        $this->common('삭제');

        return redirect(route('admin.post.index'));
    }

    public function common($type, PostRequest $request = null, Post $post = null)
    {
        if ($post) {
            $post->tags()->sync($request->getTags());
        }

        \Alert::success("정상적으로 {$type} 처리되었습니다.", "{$type}완료");
        DelTagsChange::dispatch();
        ModelChange::dispatch('post');
    }
}
