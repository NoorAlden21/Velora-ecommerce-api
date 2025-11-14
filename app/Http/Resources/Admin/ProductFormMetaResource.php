<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductFormMetaResource extends JsonResource
{
    /**
     * @param  array  $resource 
     */
    public function toArray($request)
    {
        return $this->resource;
    }
}
