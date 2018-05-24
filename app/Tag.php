<?php

namespace App;

class Tag extends Model
{
    protected $fillable = ['name', 'slug'];

    public function posts()
    {
        return $this->belongsToMany(Post::class)->withTimestamps();
    }
}
