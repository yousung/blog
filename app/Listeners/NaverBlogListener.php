<?php

namespace App\Listeners;

use App\Post;
use App\Events\NaverBlog;
use App\Events\ModelChange;

class NaverBlogListener
{
    private $isSecret;

    public function __construct()
    {
        $this->isSecret = 'production' === \App::environment();
    }

    public function handle(NaverBlog $event)
    {
        $type = $event->type;
        $post = $event->post;

        if (!$post instanceof \App\Post) {
            \Log::warning('Not Post');
            return false;
        }

        if (method_exists($this, $type)) {
            $this->{$type}($post);
        }
    }

    /*
     * Naver Blog New Post
     */
    private function new(Post $post)
    {
        // 이미 naver에 작성된 상황이면 수정으로 변경
        if ($postId = $post->getPostId()) {
            return $this->edit($postId);
        }

        $postId = \NaverBlog::NewBlog($post);

        $post->naver = $postId ?? null;
        $post->save();

        ModelChange::dispatch('post');
    }

    /*
     * Naver Blog Post Delete & New Post
     */
    private function edit(Post $post)
    {
        // 네이버에 작성된 것이 없을 경우 새로 작성으로 변경
        if (!$post->getPostId()) {
            return $this->new($post);
        }

        //삭제
        $this->del($post);
        $post->naver = null;
        $post->save();

        $this->new($post);
    }

    private function del(Post $post)
    {
        // 등록된 네이버 블로그가 없는 경우 취소
        if (!$post->getPostId()) {
            return false;
        }

        \NaverBlog::DelBlog($post);
    }
}
