<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

class SEOController extends Controller implements Cacheble
{
    public function cacheTags()
    {
        return ['post', 'seo', 'series', 'tag'];
    }

    private function getPost()
    {
        $query = \App\Post::latest();

        return $this->cache(cache_key('post.seo'), $query, 'get');
    }

    public function feed()
    {
        $posts = $this->getPost();

        $now = Carbon::now();

        return response(view('seo.feed', compact('posts', 'now')))
            ->header('Content-Type', 'text/xml');
    }

    public function sitemap()
    {
        $sitemap = \App::make('sitemap');
        $key = md5(env('APP_NAME').'-seo-site-map');
        $sitemap->setCache($key, 3600);

        if (!$sitemap->isCached()) {
            foreach ($this->getPost() as $post) {
                $images = [];
                foreach (get_images($post) as $image) {
                    $images[] = [
                        'url' => $image,
                        'title' => $post->title,
                        'caption' => $post->subTitle,
                    ];
                }


                $sitemap->add(
                    route('post.show', optimus($post->id)),
                    Carbon::parse($post->updated_at),
                    1,
                    'daily',
                    $images,
                    $post->title
                );
            }
        }

        return $sitemap->render('xml');
    }

    public function syndication()
    {
        //1
    }
}
