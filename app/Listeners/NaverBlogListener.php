<?php

namespace App\Listeners;

use App\Events\NaverBlog;
use App\Events\ModelChange;
use Illuminate\Contracts\Queue\ShouldQueue;

class NaverBlogListener implements ShouldQueue
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

        if (method_exists($this, $type)) {
            $this->{$type}($post);
        }
    }

    /*
     * Naver Blog New Post
     */
    private function new($post)
    {
        // 이미 naver에 작성된 상황이면 수정으로 변경
        if ($post->naver) {
            return $this->edit($post);
        }

        $context = $this->changeContext($post);
        $category = optional($post->series)->title;
        $tags = count($post->tags) ? implode(',', optional($post->tags)->pluck('name')->toArray()) : [];
        //$this->naverBlog->newBlog($post->title, $context, $category, $tags);
        $postId = \NaverBlog::NewBlog($this->isSecret, $post->title, $context, $category, $tags);

        $post->naver = $postId ?? null;
        $post->save();

        ModelChange::dispatch('post');
    }

    private function changeContext($post)
    {
        $postUrl = route('post.show', optimus($post->id));
        $context = nl2br($post->context);
        $context = str_replace('<code', '<pre', $context);
        $context = str_replace('</code>', '</pre>', $context);

        $views = [];
        $views[] = "<h2>{$post->subTitle}</h2>{$context}";
        $views[] = '자세한 이야기 보러가기';
        $views[] = "<a target='_blank' href=\"{$postUrl}\">{$postUrl}</a>";

        return implode('<br/>', $views);
    }

    /*
     * Naver Blog Post Delete & New Post
     */
    private function edit($post)
    {
        // 네이버에 작성된 것이 없을 경우 새로 작성으로 변경
        if (!$post->naver) {
            return $this->new($post);
        }

        //삭제
        $this->del($post);
        $post->naver = null;
        $post->save();

        $this->new($post);
    }

    private function del($post)
    {
        // 등록된 네이버 블로그가 없는 경우 취소
        if (!$post->naver) {
            return false;
        }

        \NaverBlog::DelBlog($post->naver);
    }
}
