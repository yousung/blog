<?php

namespace App\Http\Controllers;

use App\Events\ModelChange;
use App\Post;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Spatie\SchemaOrg\Schema;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $cache;

    public function __construct()
    {
        $this->cache = app('cache');
        if (method_exists($this, 'cacheTags')) {
            $this->cache = app('cache')->tags($this->cacheTags());
        }
    }

    // 캐시
    protected function cache($key, $query, $method, ...$args)
    {
        $args = (!empty($args)) ? implode(',', $args) : null;
        if (false === env('CACHE')) {
            return $query->{$method}($args);
        }

        return $this->cache->remember($key, 120, function () use ($query, $method, $args) {
            return $args ? $query->{$method}($args) : $query->{$method}();
        });
    }

    protected function hitUp($model)
    {
        $key = md5($model->id);
        if (!\Session::has($key)) {
            \Session::put($key, true);
            $model->increment('hit');
        }

        if (method_exists($this, 'cacheTags')) {
            ModelChange::dispatch($this->cacheTags());
        }
    }

    protected function schema($title, $description)
    {
//        Schema::
    }

    protected function seo($title, $description, Post $post = null)
    {
        \SEO::setTitle($title);
        \SEO::setDescription(noTag($description));
        if ($post) {
            if (count($post->tags)) {
                \SEOMeta::setKeywords($post->tags->pluck('name')->toArray());
            }

            if ($series = $post->series) {
                \SEO::setCanonical(route('post.index').'?series='.$series->slug);
            }

            if ($context = $post->context) {
                foreach (get_images($post) as $image) {
                    \OpenGraph::addImage($image);
                }
            }
        }
    }
}
