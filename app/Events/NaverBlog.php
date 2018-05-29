<?php

namespace App\Events;

use App\Post;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class NaverBlog
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $type;
    public $post;

    public function __construct($type = 'new', Post $post)
    {
        $this->type = $type;
        $this->post = $post;
    }
}
