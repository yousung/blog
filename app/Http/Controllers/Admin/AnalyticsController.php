<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Spatie\Analytics\Period;
use App\Http\Controllers\Controller;
use App\Http\Requests\StatisticRequest;

class AnalyticsController extends Controller
{
    private $divs = ['vist', 'engine', 'keyword', 'os'];

    // 페이지 분기
    public function index(StatisticRequest $request, $type)
    {
        if (!in_array($type, $this->divs)) {
            abort(404);
        }

        $start_date = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->firstOfMonth();

        $end_date = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now()->endOfDay();

        $days = Period::create($start_date, $end_date);
        \View::share(compact('start_date', 'end_date'));

        return $this->{$type}($days);
    }

    private function os($days)
    {
        $metrics = 'ga:sessions';
        $dimensions = 'ga:operatingSystem,ga:operatingSystemVersion,ga:browser,ga:browserVersion';
        $sort = '-ga:sessions';
        $os = \Analytics::performQuery($days, $metrics, [
            'dimensions' => $dimensions,
            'sort' => $sort,
        ])['rows'];

        return view('admin.analytics.os', compact('os'));
    }

    private function keyword($days)
    {
        //키워드 검색
        $metrics = 'ga:sessions,ga:pageviews';
        $dimensions = 'ga:keyword';
        $sort = '-ga:sessions';
        $filters = 'ga:keyword!~(not)';
        $keyword = \Analytics::performQuery($days, $metrics, [
            'filters' => $filters,
            'dimensions' => $dimensions,
            'sort' => $sort,
            'max-results' => 10,
        ])['rows'];

        $keyword = $keyword ?? [];

        return view('admin.analytics.keyword', compact('keyword'));
    }

    private function vist($days)
    {
        $count = \Analytics::fetchTotalVisitorsAndPageViews($days);

        return view('admin.analytics.vist', compact('count'));
    }

    private function engine($days)
    {
        //검색 엔진
        $metrics = 'ga:sessionDuration,ga:pageviews,ga:exits';
        $dimensions = 'ga:source';
        $filters = 'ga:medium==cpa,ga:medium==cpc,ga:medium==cpm,ga:medium==cpp,ga:medium==cpv,ga:medium==organic,ga:medium==ppc';
        $sort = '-ga:pageviews';
        $searchEngine = \Analytics::performQuery($days, $metrics, [
            'filters' => $filters,
            'dimensions' => $dimensions,
            'sort' => $sort, ])['rows'];

        $searchEngine = $searchEngine ?? [];

        return view('admin.analytics.engine', compact('searchEngine'));
    }
}
