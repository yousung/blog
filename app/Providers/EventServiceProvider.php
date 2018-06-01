<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\ModelChange' => [
            'App\Listeners\ModelChangeListener',
        ],

        'App\Events\DelTagsChange' => [
            'App\Listeners\DelTagsListener',
        ],

        'App\Events\EmailSender' => [
            'App\Listeners\EmailSenderListener',
        ],

        // 네이버 블로그 관련
        'App\Events\NaverBlog' => [
            'App\Listeners\NaverBlogListener',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot()
    {
        parent::boot();
    }
}
