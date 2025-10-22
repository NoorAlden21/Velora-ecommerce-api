<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $fillable = [
        'name', 'slug', 'type', 'is_filterable', 'is_visible', 'position'
    ];

    public function values()
    {
        return $this->hasMany(AttributeValue::class)->orderBy('position');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_attributes')->withTimestamps()->withPivot('position');
    }
}
