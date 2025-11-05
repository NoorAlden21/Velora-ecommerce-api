<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'slug'      => $this->slug,
            'sku'       => $this->sku,
            'active'    => (bool) $this->is_active,
            'published_at' => $this->published_at?->toIso8601String(),

            'price' => [
                'cents'    => $this->price_cents,
                'currency' => $this->currency,
            ],

            'brand' => $this->whenLoaded('brand', fn () => [
                'id'   => $this->brand->id,
                'slug' => $this->brand->slug,
                'name' => $this->brand->name,
            ]),

            'primary_category' => $this->whenLoaded('primaryCategory', fn () => new CategoryResource($this->primaryCategory)),
            'categories'       => CategoryResource::collection($this->whenLoaded('categories')),
            'audiences'        => AudienceResource::collection($this->whenLoaded('audiences')),

            // Options with only the values selected for this product
            'options' => $this->when(
                $this->relationLoaded('options') && $this->relationLoaded('selectedOptionValues'),
                function () {
                    $byOption = $this->selectedOptionValues->groupBy(fn ($v) => $v->pivot->option_id);
                    return $this->options->map(function ($opt) use ($byOption) {
                        $vals = $byOption->get($opt->id, collect());
                        return [
                            'id'    => $opt->id,
                            'slug'  => $opt->slug,
                            'name'  => $opt->name,
                            'type'  => $opt->type,
                            'values' => $vals->map(fn ($v) => [
                                'id' => $v->id,
                                'slug' => $v->slug,
                                'name' => $v->name,
                                'code' => $v->code,
                            ])->values(),
                        ];
                    })->values();
                }
            ),

            // Variants with pairs
            'variants' => $this->whenLoaded('variants', function () {
                return VariantResource::collection($this->variants);
            }),

            // 'variants' => $this->whenLoaded('variants', function () {
            //     return $this->variants->map(function ($v) {
            //         return [
            //             'id'          => $v->id,
            //             'variant_key' => $v->variant_key,
            //             'sku'         => $v->sku,
            //             'stock'       => $v->stock,
            //             'active'      => (bool) $v->is_active,
            //             'price'       => [
            //                 'cents'          => $v->price_cents,
            //                 'currency'       => $v->currency ?? $this->currency,
            //                 'effective_cents' => $v->price_cents ?? $this->price_cents,
            //             ],
            //             'pairs'       => $v->values->map(function ($pv) {
            //                 return [
            //                     'option' => [
            //                         'id'   => $pv->option->id,
            //                         'slug' => $pv->option->slug,
            //                         'name' => $pv->option->name,
            //                     ],
            //                     'value'  => [
            //                         'id'   => $pv->optionValue->id,
            //                         'slug' => $pv->optionValue->slug,
            //                         'name' => $pv->optionValue->name,
            //                         'code' => $pv->optionValue->code,
            //                     ],
            //                 ];
            //             })->values(),
            //         ];
            //     })->values();
            // }),

            // Color images grouped by option_value_id
            'color_images' => $this->whenLoaded('colorImages', function () {
                return $this->colorImages
                    ->groupBy('option_value_id')
                    ->map(fn ($group) => $group->sortBy('position')->values()->map(fn ($img) => [
                        'url' => $img->image_url,
                        'position' => $img->position,
                    ]))
                    ->map(function ($files, $optionValueId) {
                        return [
                            'option_value_id' => (int) $optionValueId,
                            'files' => $files,
                        ];
                    })->values();
            }),

            // Attributes/specs
            'attributes' => $this->whenLoaded('attributeValues', function () {
                return $this->attributeValues
                    ->groupBy('attribute_id')
                    ->map(function ($vals) {
                        $attr = $vals->first()->attribute;
                        return [
                            'id'     => $attr->id,
                            'slug'   => $attr->slug,
                            'name'   => $attr->name,
                            'values' => $vals->map(fn ($av) => [
                                'id' => $av->id,
                                'slug' => $av->slug,
                                'value' => $av->value,
                            ])->values(),
                        ];
                    })->values();
            }),

            'breadcrumbs' => $this->when(isset($this->breadcrumbs), $this->breadcrumbs),

            'description' => $this->description,

            'meta' => [
                'title'       => $this->meta_title ?? $this->name,
                'description' => $this->meta_description ?? str($this->description ?? '')->limit(150)->toString(),
            ],
        ];
    }
}
