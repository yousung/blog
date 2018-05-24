<?php

namespace App\filters;

class PostFilter extends Filter
{
    protected $filters = ['tag', 'series', 'search'];

    protected function search($search)
    {
        $this->builder->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%");
            $q->orWhere('subTitle', 'like', "%{$search}%");
            $q->orWhere('context', 'like', "%{$search}%");
        })->orWhereHas('series', function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%");
            $q->orWhere('subTitle', 'like', "%{$search}%");
        })->orWhereHas('tags', function ($q) use ($search) {
            $slug = make_slug($search);
            $q->where('name', 'like', "%{$search}%");
            $q->orWhere('name', 'like', "%{$slug}%");
        });
    }

    protected function series($series)
    {
        $this->builder->whereHas('series', function ($q) use ($series) {
            $slug = make_slug($series);
            $q->where('slug', $slug);
            $q->orWhere('title', $series);
            $q->orWhere('subTitle', $series);
        });

        $this->builder->orderBy('orderBy', 'asc');
    }

    protected function tag($tag)
    {
        $this->builder->whereHas('tags', function ($q) use ($tag) {
            $slug = make_slug($tag);
            $q->where('slug', $slug);
            $q->orWhere('name', $tag);
        });
    }

    protected function init()
    {
        return $this->builder
            ->with(['tags', 'user', 'series'])
            ->orderBy('created_at', 'desc');
    }
}
