<?php

namespace App\Http\Controllers\Admin;

use App\Series;
use App\Events\ModelChange;
use App\filters\SeriesFilter;
use App\Http\Controllers\Cacheble;
use App\Http\Controllers\Controller;
use App\Http\Requests\SeriesRequest;

class SeriesController extends Controller implements Cacheble
{
    public function cacheTags()
    {
        return 'series';
    }

    public function index(SeriesFilter $filter)
    {
        $query = Series::Filter($filter);

        $series = $this->cache(cache_key('admin.series.index'), $query, 'paginate', 15);

        return view('admin.series.index', compact('series'));
    }

    public function show(Series $series)
    {
        return view('admin.series.show', compact('series'));
    }

    public function create()
    {
        $series = new Series();

        return view('admin.series.create', compact('series'));
    }

    public function store(SeriesRequest $request)
    {
        $series = Series::create($request->getData());
        $this->common('작성');

        return redirect(route('admin.series.show', $series->id));
    }

    public function edit(Series $series)
    {
        return view('admin.series.edit', compact('series'));
    }

    public function update(SeriesRequest $request, Series $series)
    {
        $series->update($request->getData());

        $this->common('작성');

        return redirect(route('admin.series.show', $series->id));
    }

    public function destroy(Series $series)
    {
        $this->common('삭제');
        $series->delete();

        return redirect(route('admin.series.index'));
    }

    public function common($type)
    {
        \Alert::success("정상적으로 {$type} 처리되었습니다.", "{$type}완료");
        ModelChange::dispatch('series');
    }
}
