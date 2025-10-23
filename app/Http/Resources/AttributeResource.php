<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'slug'         => $this->slug,
            'type'         => $this->type,
            'is_filterable' => (bool) $this->is_filterable,
            'is_visible'   => (bool) $this->is_visible,
            'position'     => $this->position,
            'values'       => AttributeValueResource::collection($this->whenLoaded('values')),
        ];
    }
}
