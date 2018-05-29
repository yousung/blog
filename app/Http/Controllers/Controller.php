<?php

namespace App\Http\Controllers;

use App\Post;
use App\Events\ModelChange;
use Carbon\Carbon;
use Spatie\SchemaOrg\Schema;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

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

    protected function postList($posts)
    {
        $item = [];
        foreach ($posts as $idx => $post) {
            $item[] = Schema::listItem()
                ->position($idx + 1)
                ->item(Schema::thing()->name($post->title)->image(get_images($post)));
        }

        $itemListSchma = Schema::breadcrumbList()->itemListElement($item);

        view()->share(compact('itemListSchma'));
    }

    protected function schema(Post $post = null)
    {
        $image = get_images($post);
        $person = $this->schemePerson();

        $itemSchma = $post
            ? Schema::blog()->name($post->title)
            ->headline($post->subTitle)
            ->publisher($person)
            ->mainEntityOfPage(\URL::full())
            ->author(optional($post->user)->name)
            ->datePublished($post->created_at->toDateString())
            ->image($image)
            ->dateModified($post->updated_at->toDateString())
            : Schema::blog()->name('러비쥬')
                ->headline('감성 개발자 러비쥬 블로그입니다')
                ->publisher($person)
                ->mainEntityOfPage(\URL::full())
                ->author('러비쥬')
//                ->datePublished(Carbon::now()->toDateString())
                ->image($image);
//                ->dateModified(Carbon::now()->toDateString());

        view()->share(compact('itemSchma'));
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
                foreach (array_reverse(get_images($post)) as $image) {
                    \OpenGraph::addImage($image);
                }
            }
        }
    }

    private function schemePerson()
    {
        $logo = $this->schemeLogo();

        return Schema::organization()
            ->name('Lovizu')
            ->logo($logo);
    }

    private function schemeLogo()
    {
        return Schema::imageObject()->url(\URL::to('/images/yousung.jpg'))
            ->name('Lovizu')
            ->height('640')
            ->width('640');
    }
}
