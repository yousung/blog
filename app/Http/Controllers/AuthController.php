<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthReqeust;

class AuthController extends Controller
{
    public function login()
    {
        return view('auth.login');
    }

    public function store(AuthReqeust $reqeust)
    {
        if (\Auth::attempt($reqeust->only('email', 'password'), $reqeust->has('remember'))) {
            // TODO : alert
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
