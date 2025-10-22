<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audience extends Model
{
    protected $fillable = [
        'name', 'slug', 'is_active',
    ];

    // لاحقاً بعد إنشاء Product:
    // public function products()
    // {
    //     return $this->belongsToMany(Product::class, 'product_audiences')
    //                 ->withTimestamps();
    // }
}
