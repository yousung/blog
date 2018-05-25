<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthReqeust;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    public function login()
    {
        return view('auth.login');
    }

    public function store(AuthReqeust $request)
    {
        if (\Auth::attempt($request->only('email', 'password'), $request->has('remember'))) {
            \Auth::logoutOtherDevices($request->input('password'));

            \Alert::success('관리자님 환영합니다', '로그인 성공')->autoclose(5000);

            return redirect(route('admin.admin'));
        }
    }

    public function logout()
    {
        \Session::flush();
        \Auth::logout();

        return redirect(route('admin.admin'));
    }
}
