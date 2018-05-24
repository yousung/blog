<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Spatie\Analytics\Period;

class PageController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard');
    }

    public function api()
    {
        $data = \Cache::tags(['post', 'subscribe'])->remember(cache_key('api.index'), 120, function () {
            $data = [];
            $data['post'] = number_format(\App\Post::all()->count());
            $data['subscribe'] = number_format(\App\Subscribe::all()->count());

            return $data;
        });

        $data = $this->googleApi($data);

        return response()->json($data, 200, [], JSON_PRETTY_PRINT);
    }

    public function googleApi($data)
    {
        $week = Period::create(Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->endOfDay());
        $counts = \Analytics::fetchTotalVisitorsAndPageViews($week);
        $date = [];
        $weekCnt = 0;
        $lastCnt = 0;
        foreach ($counts as $idx => $count) {
            $weekCnt += $count['visitors'];
            $date['cnt'][] = $count['visitors'];
            $date['date'][] = $count['date']->toDateString();
            if ($idx === count($counts) - 1) {
                $lastCnt = $count['visitors'];
            }
        }

        return array_merge($data, [
            'weekCnt' => number_format($weekCnt),
            'lastCnt' => number_format($lastCnt),
            'week' => $date
        ]);
    }
}
