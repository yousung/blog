<?php

namespace App\filters;

class SeriesFilter extends Filter
{
    protected $filters = ['query', 'search'];

    protected function query($query)
    {
        $this->builder->where('slug', make_slug($query));
    }

    protected function search($query)
    {
        $this->builder->where('slug', 'like', "%$query%")
            ->orWhere('title', 'like', "%$query%")
            ->orWhere('subTitle', 'like', "%$query%");
    }

    protected function init()
    {
        return $this->builder
            ->with('posts')
            ->withCount('posts')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
    }
}
