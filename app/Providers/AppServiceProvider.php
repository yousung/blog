<?php

namespace App\Providers;

use Jenssegers\Optimus\Optimus;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        \Carbon\Carbon::setLocale('ko');
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        if ('production' !== $this->app->environment()) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }

        $this->app->singleton(\Jenssegers\Optimus\Optimus::class, function () {
            return new Optimus(721365067, 667771235, 895521477);
        });
    }
}
