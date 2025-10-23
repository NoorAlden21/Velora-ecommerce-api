<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name', 'slug', 'parent_id', 'path', 'depth', 'position', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'depth'     => 'integer',
        'position'  => 'integer',
    ];

    // Self relations
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('position');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_categories')->withTimestamps();
    }

    public function primaryProducts()
    {
        return $this->hasMany(Product::class, 'primary_category_id');
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'category_attributes')
            ->withTimestamps()
            ->withPivot('position')
            ->orderBy('category_attributes.position');
    }


    //scopes

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('name');
    }

    //root -> this
    public function breadcrumbs(): array
    {
        $trail = [];
        $node = $this;
        while ($node) {
            array_unshift($trail, $node);
            $node = $node->parent;
        }
        return $trail;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
