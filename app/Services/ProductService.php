<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class ProductService
{
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {

            if (empty($data['slug'])) {
                $data['slug'] = $this->makeUniqueSlug($data['name']);
            } else {
                $data['slug'] = Str::slug($data['slug']);
                $data['slug'] = $this->ensureUnique($data['slug']);
            }

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

    private function makeUniqueSlug(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'item';
        }

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

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {

            $oldSlug = $product->slug;
            $wantsSlugChange = array_key_exists('slug', $data) && $data['slug'] !== $oldSlug;

            // ignore change if not allowed
            if ($wantsSlugChange && empty($data['force_slug_update'])) {
                unset($data['slug']);
                $wantsSlugChange = false;
            }

            $product->update($data);

            if ($wantsSlugChange) {
                Redirect::create([
                    'model_type' => Product::class,
                    'model_id'   => $product->id,
                    'from_slug'  => $oldSlug,
                    'to_slug'    => $product->slug,
                ]);
            }

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
