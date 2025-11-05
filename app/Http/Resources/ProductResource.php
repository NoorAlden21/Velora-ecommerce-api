<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
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

            'description'       => $this->description,

            'meta' => [
                'title'       => $this->meta_title ?? $this->name,
                'description' => $this->meta_description ?? str($this->short_description ?? '')->limit(150)->toString(),
            ],
        ];
    }
}
