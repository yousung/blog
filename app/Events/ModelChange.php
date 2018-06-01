<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ModelChange
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $modelName;

    public function __construct($modelName)
    {
        $this->modelName = $modelName;
    }
}
