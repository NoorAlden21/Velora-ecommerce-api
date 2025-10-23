<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'sku', 'is_active', 'published_at',
        'price_cents', 'currency',
        'primary_category_id',
        'description',
        'meta_title', 'meta_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function primaryCategory()
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
    }

    //other categories
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_categories')
            ->withTimestamps();
    }

    public function audiences()
    {
        return $this->belongsToMany(Audience::class, 'product_audiences')
            ->withTimestamps();
    }

    public function options()
    {
        return $this->belongsToMany(Option::class, 'product_options')->withTimestamps();
    }

    // Selected option values for this product (drives generation & UI)
    public function selectedOptionValues()
    {
        return $this->belongsToMany(OptionValue::class, 'product_option_values')
            ->withPivot('option_id')
            ->withTimestamps();
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function variantValues()
    {
        return $this->hasManyThrough(ProductVariantValue::class, ProductVariant::class);
    }

    public function colorImages() // لو اعتبرت اللون OptionValue
    {
        return $this->hasMany(ProductOptionValueImage::class);
    }



    // Attributes
    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'product_attribute_values')
            ->withPivot('attribute_id')
            ->withTimestamps();
    }

    // Slug binding
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
