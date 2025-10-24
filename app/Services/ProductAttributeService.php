<?php

namespace App\Services;

use App\Models\AttributeValue;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductAttributeService
{
    public function assignAttributeValues(Product $product, array $data): void
    {
        DB::transaction(function () use ($product, $data) {
            $valueIds = collect($data['attribute_value_ids'])->unique()->values();
            $values   = AttributeValue::with('attribute')->whereIn('id', $valueIds)->get();

            // Build tuples group by attribute_id
            $tuples = [];
            $byAttr = [];
            foreach ($values as $v) {
                $tuples[] = [
                    'product_id'        => $product->id,
                    'attribute_id'      => $v->attribute_id,
                    'attribute_value_id' => $v->id,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ];
                $byAttr[$v->attribute_id][] = $v->id;
            }

            // remove rows for provided attribute_ids that are not in new set
            $attributeIds = array_keys($byAttr);
            DB::table('product_attribute_values')
                ->where('product_id', $product->id)
                ->whereIn('attribute_id', $attributeIds)
                ->whereNotIn('attribute_value_id', $valueIds)
                ->delete();

            // Upsert
            DB::table('product_attribute_values')->upsert(
                $tuples,
                ['product_id', 'attribute_id', 'attribute_value_id'],
                [] // no updates
            );
        });
    }
}
