<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Option;
use App\Models\Audience;
use App\Models\Attribute;
use Illuminate\Support\Collection;

class ProductFormMetaService
{
    public function get(): array
    {
        // Brands
        $brands = Brand::query()
            ->select(['id', 'name', 'slug', 'is_active'])
            ->orderBy('name')
            ->get();

        // Categories
        $categories = Category::query()
            ->select(['id', 'name', 'slug', 'parent_id', 'path', 'depth', 'position', 'is_active'])
            ->orderBy('path')
            ->get();

        // Options + Values
        $options = Option::query()
            ->select(['id', 'name', 'slug', 'type', 'is_active', 'position'])
            ->with([
                'values:id,option_id,name,slug,code,position'
            ])
            ->orderBy('position')
            ->get();

        // Audiences
        $audiences = Audience::query()
            ->select(['id', 'name', 'slug', 'is_active'])
            ->orderBy('name')
            ->get();

        // Attributes
        $attributes = Attribute::query()
            ->select(['id', 'name', 'slug', 'type', 'is_filterable', 'is_visible', 'position'])
            ->with(['values:id,attribute_id,value,slug,position'])
            ->orderBy('position')
            ->get();

        $colorOptionId = optional(
            $options->first(fn ($o) => $o->slug === 'color') ??
                $options->first(fn ($o) => $o->type === 'color')
        )->id;

        return [
            'version' => now()->toDateTimeString(),

            'meta' => [
                'default_currency' => 'EUR',
                'status_options'   => ['draft', 'active'],
                'validation' => [
                    'title' => ['min' => 1, 'max' => 120],
                    'slug'  => ['pattern' => '^[a-z0-9-]+$', 'min' => 1, 'max' => 160],
                    'price' => ['min' => 0],
                    'seo_title' => ['min' => 1, 'max' => 70],
                    'seo_description' => ['min' => 1, 'max' => 160],
                ],
            ],

            'brands' => $brands->values()->all(),

            'categories' => [
                'flat' => $categories->map(fn ($c) => [
                    'id'         => $c->id,
                    'name'       => $c->name,
                    'path'       => $c->path,
                    'depth'      => (int) $c->depth,
                    'position'   => (int) $c->position,
                    'is_active'  => (bool) $c->is_active,
                    'parent_id'  => $c->parent_id,
                ])->values()->all(),
                'tree' => $this->buildTree($categories),
            ],

            'options' => $options->map(function ($o) {
                return [
                    'id'         => $o->id,
                    'name'       => $o->name,
                    'slug'       => $o->slug,
                    'type'       => $o->type,
                    'is_active'  => (bool) $o->is_active,
                    'position'   => (int) $o->position,
                    'values'     => $o->values->map(fn ($v) => [
                        'id'         => $v->id,
                        'option_id'  => $v->option_id,
                        'name'       => $v->name,
                        'slug'       => $v->slug,
                        'code'       => $v->code,
                        'position'   => (int) $v->position,
                    ])->values()->all(),
                ];
            })->values()->all(),

            'audiences' => $audiences->values()->all(),

            'attribute_catalog' => $attributes->map(function ($a) {
                $item = [
                    'id'            => $a->id,
                    'key'           => $a->slug,
                    'label'         => $a->name,
                    'type'          => $a->type,
                    'is_filterable' => (bool) $a->is_filterable,
                    'is_visible'    => (bool) $a->is_visible,
                ];

                if (in_array($a->type, ['select', 'multiselect'])) {
                    $item['allowed_values'] = $a->values
                        ->sortBy('position')
                        ->map(fn ($v) => [
                            'id'    => $v->id,
                            'value' => $v->value,
                            'slug'  => $v->slug,
                        ])->values()->all();
                }

                return $item;
            })->values()->all(),

            'defaults' => [
                'status'          => 'draft',
                'currency'        => 'EUR',
                'color_option_id' => $colorOptionId,
            ],

            'upload' => [
                'max_mb' => (int) (config('files.upload_max_mb', 10)),
                'accept' => ['image/jpeg', 'image/png', 'image/webp'],
            ],
        ];
    }

    private function buildTree(Collection $categories): array
    {
        $byParent = $categories->groupBy('parent_id');

        $build = function ($parentId) use (&$build, $byParent) {
            return ($byParent[$parentId] ?? collect())
                ->sortBy(['position', 'name'])
                ->map(function ($c) use ($build) {
                    return [
                        'id'        => $c->id,
                        'name'      => $c->name,
                        'slug'      => $c->slug,
                        'path'      => $c->path,
                        'depth'     => (int) $c->depth,
                        'position'  => (int) $c->position,
                        'is_active' => (bool) $c->is_active,
                        'children'  => $build($c->id),
                    ];
                })
                ->values()
                ->all();
        };

        return $build(null);
    }
}
