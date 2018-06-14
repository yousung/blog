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

    public function getPostCategoryAttribute()
    {
        return optional($this->series)->title;
    }

    public function getPostContextAttribute()
    {
        $postUrl = route('post.show', optimus($this->id));
        $context = $this->context;
//        $context = nl2br($context);

        $views = [];
        $views[] = "<h2>{$this->subTitle}</h2>{$context}";
        $views[] = '자세한 이야기 보러가기';
        $views[] = "<a target='_blank' href=\"{$postUrl}\">{$postUrl}</a>";

        return implode('<br/>', $views);
    }

    public function getPostTagsAttribute()
    {
        return count($this->tags)
            ? implode(',', optional($this->tags)->pluck('name')->toArray())
            : [];
    }
}
