<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            'Men'   => ['Tops', 'Bottoms', 'Shoes'],
            'Women' => ['Tops', 'Bottoms', 'Shoes'],
            'Kids'  => ['Tops', 'Bottoms', 'Shoes'],
        ];

        foreach ($tree as $rootName => $children) {
            $rootSlug = Str::slug($rootName);
            $root = Category::updateOrCreate(
                ['slug' => $rootSlug],
                [
                    'name' => $rootName,
                    'parent_id' => null,
                    'path' => $rootSlug,
                    'depth' => 0,
                    'position' => 0,
                    'is_active' => true,
                ]
            );

            foreach ($children as $i => $childName) {
                $childSlug = Str::slug($childName);
                Category::updateOrCreate(
                    ['slug' => "{$rootSlug}-{$childSlug}"],
                    [
                        'name' => $childName,
                        'parent_id' => $root->id,
                        'path' => $root->path . '/' . $childSlug,
                        'depth' => 1,
                        'position' => $i,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
