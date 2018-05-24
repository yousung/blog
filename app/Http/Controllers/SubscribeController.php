<?php

namespace App\Http\Controllers;

use App\Subscribe;
use App\Http\Requests\SubscribeRequest;

class SubscribeController extends Controller
{
    public function create()
    {
        $this->seo('구독신청', '구독 신청을 하시면 새로운 게시물이 나올 때마다 알려드립니다');

        return view('subscribe');
    }

    public function store(SubscribeRequest $request)
    {
        $subscribe = Subscribe::create($request->all());

        \Alert::success("{$subscribe->name} 님의 구독이 신청되었습니다.", '구독신청완료');
        return redirect(route('home'));
    }

    public function destory($code, $email)
    {

        $where = [
            'id' => optimus()->decode($code),
            'email' => $email
        ];


        if ($subscribe = Subscribe::where($where)->first()) {
            $subscribe->delete();

            // TODO :: alert
            return redirect(route('home'));
        }
    }
}
