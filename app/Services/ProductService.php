<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create($data);

            if (!empty($data['primary_category_id'])) {
                $product->categories()->syncWithoutDetaching([$data['primary_category_id']]);
            }

            if (!empty($data['category_ids'])) {
                $product->categories()->syncWithoutDetaching($data['category_ids']);
            }

            if (!empty($data['audience_ids'])) {
                $product->audiences()->sync($data['audience_ids']);
            }

            return $product->load(['primaryCategory', 'categories', 'audiences']);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->update($data);

            if (array_key_exists('category_ids', $data)) {
                $product->categories()->sync($data['category_ids'] ?? []);
                if (!empty($data['primary_category_id'])) {
                    $product->categories()->syncWithoutDetaching([$data['primary_category_id']]);
                }
            }

            if (array_key_exists('audience_ids', $data)) {
                $product->audiences()->sync($data['audience_ids'] ?? []);
            }

            return $product->load(['primaryCategory', 'categories', 'audiences']);
        });
    }

    public function destroy(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $product->categories()->detach();
            $product->audiences()->detach();
            $product->delete();
        });
    }
}
