<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Subscribe extends Model
{
    protected $fillable = ['email', 'name'];

    public function getEmailAttribute($key)
    {
        return decrypt($key);
    }

    public function getNameAttribute($key)
    {
        return decrypt($key);
    }
}
