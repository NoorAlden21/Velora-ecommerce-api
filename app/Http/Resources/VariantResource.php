<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VariantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'variant_key' => $this->variant_key,
            'sku'         => $this->sku,
            'stock'       => $this->stock,
            'active'      => (bool) $this->is_active,
            'price'       => [
                'cents'          => $this->price_cents,
                'currency'       => $this->currency ?? $this->product->currency,
                'effective_cents' => $this->price_cents ?? $this->product->price_cents,
            ],
            'pairs'       => $this->whenLoaded('values', function () {
                return $this->values->map(function ($pv) {
                    return [
                        'option' => ['id' => $pv->option->id, 'slug' => $pv->option->slug, 'name' => $pv->option->name],
                        'value'  => ['id' => $pv->optionValue->id, 'slug' => $pv->optionValue->slug, 'name' => $pv->optionValue->name, 'code' => $pv->optionValue->code],
                    ];
                })->values();
            }),
        ];
    }
}
