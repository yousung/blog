<?php

namespace App\Http\Controllers;

use App\filters\SeriesFilter;
use App\Series;

class SeriesController extends Controller implements Cacheble
{
    public function cacheTags()
    {
        return ['post', 'series'];
    }

    public function index(SeriesFilter $filter)
    {
        $this->seo('시리즈 보기', '시리즈 목록을 확인합니다');

        $query = Series::Filter($filter);

        $series = $this->cache(cache_key('series.index'), $query, 'get');

        return view('series.index', compact('series'));
    }
}
