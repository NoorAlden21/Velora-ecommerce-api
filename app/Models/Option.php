<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    protected $fillable = ['name', 'slug', 'type', 'is_active', 'position'];

    public function values()
    {
        return $this->hasMany(OptionValue::class)->orderBy('position');
    }
}
