<?php

namespace App\Listeners;

use App\Events\NaverBlog;
use App\Post;
use Lovizu\NaverXmlRpc\NaverBlogXml;

class NaverBlogListener
{
    private $isSecret;
    private $naver;

    public function __construct()
    {
        $this->isSecret = 'production' === \App::environment();
    }

    public function handle(NaverBlog $event)
    {
        $type = $event->type;
        $post = Post::findOrFail($event->post);

        $this->naver = new NaverBlogXml(env('NAVER-BLOG-ID'), env('NAVER-BLOG-PASS'));

        if ('del' === $type) {
            return $this->naver->delBlog($post->naver);
        }

        $this->naver->setItem($post->title, $post->postContext);

        // 카테고리
        if ($category = $post->postCategory) {
            $this->naver->setCategory($category);
        }

        // 태그
        if ($tags = $post->postTags) {
            $this->naver->setTags($tags);
        }

        // 비공개
        if ('local' === \App::environment()) {
            $this->naver->setSecret();
        }

        return $this->naver->post($post->naver);
    }
}
