<?php

namespace App\Listeners;

use App\Events\DelTagsChange;

class DelTagsListener
{
    public function handle(DelTagsChange $event)
    {
        $tags = \App\Tag::withCount('posts')->get();

        foreach ($tags as $tag) {
            if (!$tag->posts_count) {
                $tag->delete();
            }
        }
    }
}
