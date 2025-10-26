<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Redirect;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Contracts\Database\Eloquent\Builder;

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

    public function indexQuery(array $params): Builder
    {
        /** @var Builder $q */
        $q = Product::query()
            ->with(['primaryCategory', 'categories', 'audiences'])
            ->where('is_active', true);

        // Text search
        if (!empty($params['q'])) {
            $term = $params['q'];
            $q->where(function ($qq) use ($term) {
                $qq->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        // Primary category
        if (empty($params['category_id']) && !empty($params['category_slug'])) {
            $params['category_id'] = Category::where('slug', $params['category_slug'])->value('id');
        }

        if (!empty($params['category_id'])) {
            $q->where('primary_category_id', (int) $params['category_id']);
        }
        // Audience
        if (!empty($params['audience_id'])) {
            $aid = (int) $params['audience_id'];
            $q->whereHas('audiences', fn ($aq) => $aq->where('audiences.id', $aid));
        }

        // Price range (in cents)
        $min = $params['price_min'] ?? null;
        $max = $params['price_max'] ?? null;
        if ($min !== null && $max !== null) {
            $q->whereBetween('price_cents', [(int)$min, (int)$max]);
        } elseif ($min !== null) {
            $q->where('price_cents', '>=', (int)$min);
        } elseif ($max !== null) {
            $q->where('price_cents', '<=', (int)$max);
        }

        // expects attrs[attribute_id] = [attribute_value_ids...]
        if (!empty($params['attrs']) && is_array($params['attrs'])) {
            foreach ($params['attrs'] as $attributeId => $valueIds) {
                if (!is_array($valueIds) || empty($valueIds)) continue;
                $q->whereHas('attributeValues', function ($qh) use ($attributeId, $valueIds) {
                    $qh->where('product_attribute_values.attribute_id', (int) $attributeId)
                        ->whereIn('product_attribute_values.attribute_value_id', array_map('intval', $valueIds));
                });
            }
        }

        // Option filters → require existence of a variant that matches the selections
        // expects options[option_id] = [option_value_ids...]
        if (!empty($params['options']) && is_array($params['options'])) {
            foreach ($params['options'] as $optionId => $valueIds) {
                if (!is_array($valueIds) || empty($valueIds)) continue;
                $q->whereHas('variantValues', function ($vv) use ($optionId, $valueIds) {
                    $vv->where('option_id', (int) $optionId)
                        ->whereIn('option_value_id', array_map('intval', $valueIds));
                });
            }
        }

        $stockMin = array_key_exists('stock_min', $params) ? (int) $params['stock_min'] : null;
        $stockMax = array_key_exists('stock_max', $params) ? (int) $params['stock_max'] : null;
        $out      = array_key_exists('out_of_stock', $params)
            ? filter_var($params['out_of_stock'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;
        $in       = array_key_exists('in_stock', $params)
            ? filter_var($params['in_stock'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        if ($stockMin !== null) {
            $q->whereHas('variants', fn ($vq) => $vq->where('stock', '>=', $stockMin));
        } elseif ($stockMax !== null) {
            $q->whereHas('variants', fn ($vq) => $vq->where('stock', '<=', $stockMax));
        } elseif ($out === true) {
            $q->whereHas('variants', fn ($vq) => $vq->where('stock', '<=', 0));
        } elseif ($in === true) {
            $q->whereHas('variants', fn ($vq) => $vq->where('stock', '>', 0));
        }

        // Sorting
        switch ($params['sort'] ?? 'latest') {
            case 'price_asc':
                $q->orderBy('price_cents');
                break;
            case 'price_desc':
                $q->orderByDesc('price_cents');
                break;
            case 'name_asc':
                $q->orderBy('name');
                break;
            case 'name_desc':
                $q->orderByDesc('name');
                break;
            default:
                // latest by published_at; fallback to id
                $q->orderByDesc('published_at')->orderByDesc('id');
                break;
        }

        return $q;
    }

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
