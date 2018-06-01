<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;
    protected $fillable = ['title', 'subTitle', 'context', 'user_id', 'series_id'];

    public function getSecretAttribute()
    {
        return optimus($this->id);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function series()
    {
        return $this->belongsTo(Series::class);
    }
}
