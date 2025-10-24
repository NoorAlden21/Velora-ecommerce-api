<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $fillable = [
        'model_type', 'model_id', 'from_slug', 'to_slug'
    ];
}
