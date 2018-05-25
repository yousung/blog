<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ComposerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (!$this->app->runningInConsole()) {
            $global = \Cache::tags('setting')->remember('global.var', 120, function () {
                return \App\Setting::first();
            });

            view()->share(compact('global'));
        }
    }

    public function register()
    {
    }
}
