<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOptionValueImage extends Model
{
    protected $fillable = ['product_id', 'option_value_id', 'image_url', 'position'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function optionValue()
    {
        return $this->belongsTo(OptionValue::class);
    }
}
