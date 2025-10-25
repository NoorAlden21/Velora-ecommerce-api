<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Redirect;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    private const PRODUCT_COLUMNS = [
        'name',
        'slug',
        'sku',
        'is_active',
        'published_at',
        'price_cents',
        'currency',
        'primary_category_id',
        'description',
        'meta_title',
        'meta_description',
    ];

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {

            $categoryIds = $data['category_ids'] ?? null;
            $audienceIds = $data['audience_ids'] ?? null;

            if (empty($data['slug'])) {
                $data['slug'] = $this->makeUniqueSlug($data['name'] ?? 'item');
            } else {
                $data['slug'] = $this->ensureUnique(Str::slug($data['slug']));
            }

            $productData = Arr::only($data, self::PRODUCT_COLUMNS);
            $product = Product::create($productData);

            if (!empty($productData['primary_category_id'])) {
                $product->categories()->syncWithoutDetaching([$productData['primary_category_id']]);
            }
            if (is_array($categoryIds)) {
                $product->categories()->syncWithoutDetaching($categoryIds);
            }
            if (is_array($audienceIds)) {
                $product->audiences()->sync($audienceIds);
            }

            return $product->load(['primaryCategory', 'categories', 'audiences']);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {

            $categoryIds = $data['category_ids'] ?? null;
            $audienceIds = $data['audience_ids'] ?? null;

            $oldSlug = $product->slug;
            $wantsSlugChange = array_key_exists('slug', $data) && $data['slug'] !== $oldSlug;

            if ($wantsSlugChange && empty($data['force_slug_update'])) {
                unset($data['slug']);
                $wantsSlugChange = false;
            }

            if ($wantsSlugChange) {
                $data['slug'] = $this->ensureUnique(Str::slug($data['slug']));
            }

            $productData = Arr::only($data, self::PRODUCT_COLUMNS);
            $product->update($productData);

            if ($wantsSlugChange) {
                Redirect::create([
                    'model_type' => Product::class,
                    'model_id'   => $product->id,
                    'from_slug'  => $oldSlug,
                    'to_slug'    => $product->slug,
                ]);
            }

            if (array_key_exists('category_ids', $data)) {
                $product->categories()->sync($categoryIds ?? []);
                if (!empty($productData['primary_category_id'])) {
                    $product->categories()->syncWithoutDetaching([$productData['primary_category_id']]);
                }
            }

            if (array_key_exists('audience_ids', $data)) {
                $product->audiences()->sync($audienceIds ?? []);
            }

            return $product->load(['primaryCategory', 'categories', 'audiences']);
        });
    }

    private function makeUniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'item';
        $reserved = ['api', 'admin', 'login', 'logout', 'register', 'me', 'products', 'categories'];
        if (in_array($base, $reserved, true)) {
            $base .= '-p';
        }
        return $this->ensureUnique($base);
    }

    private function ensureUnique(string $base): string
    {
        $slug = $base;
        $i = 2;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }
}
