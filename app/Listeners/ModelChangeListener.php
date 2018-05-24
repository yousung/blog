<?php

namespace App\Listeners;

use App\Events\ModelChange;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ModelChangeListener
{
    public function handle(ModelChange $event)
    {
        $tags = $event->modelName;

        if (is_array($tags)) {
            foreach ($tags as $tag) {
                \Cache::tags([$tag])->flush();
            }
        }

        return \Cache::tags($tags)->flush();
    }
}
