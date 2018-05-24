<?php

namespace App;

use Illuminate\Database\Eloquent\Model as Eloqurt;

class Model extends Eloqurt
{
    public function scopeFilter($query, $filter)
    {
        return $filter->apply($query);
    }

    public function getKeyAttribute()
    {
        $uniqueName = $this->getQualifiedKeyName();
        $keyName = $this->getKeyName();
        $key = $this->{$keyName};

        return md5("{$uniqueName}.{$key}");
    }
}
