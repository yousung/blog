<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['post_bg', 'show_bg', 'tag_bg', 'series_bg', 'subscribe_bg', 'search_bg', 'errors_bg'];
}
