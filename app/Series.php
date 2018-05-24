<?php

namespace App;

class Series extends Model
{
    protected $fillable = ['title', 'subTitle', 'user_id', 'slug'];

    public function posts()
    {
        return $this->hasMany(Post::class)->orderBy('created_at', 'asc');
    }
}
