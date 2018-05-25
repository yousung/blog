<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
//use Spatie\SchemaOrg\Schema;

class ComposerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (!$this->app->runningInConsole()) {
            $global = \Cache::tags('setting')->remember('global.var', 120, function () {
                return \App\Setting::first();
            });

//            Schema::

            view()->share(compact('global'));
        }
    }

    public function register()
    {
    }
}
