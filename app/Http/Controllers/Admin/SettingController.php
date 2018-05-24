<?php

namespace App\Http\Controllers\Admin;

use App\Events\ModelChange;
use App\Setting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit(Setting $setting)
    {
        return view('admin.setting.edit', compact('setting'));
    }

    public function update(Request $request, Setting $setting)
    {
        $setting->update($request->all());

        \Alert::success('정상적으로 처리되었습니다.', '수정완료');

        ModelChange::dispatch('setting');

        return back();
    }
}
