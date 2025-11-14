<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Option;
use App\Models\OptionValue;
use App\Models\Product;
use App\Models\ProductOptionValueImage;
use App\Models\ProductVariantValue;
use App\Models\Redirect;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

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
        'brand_id',
        'primary_category_id',
        'description',
        'meta_title',
        'meta_description',
    ];

    public function indexQuery(array $params): Builder
    {
        /** @var Builder $q */
        $q = Product::query()
            ->with(['brand', 'primaryCategory', 'categories', 'audiences']);

        // Text search
        if (!empty($params['q'])) {
            $term = $params['q'];
            $q->where(function ($qq) use ($term) {
                $qq->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        // Brand
        if (empty($params['brand_id']) && !empty($params['brand_slug'])) {
            $params['brand_id'] = Brand::where('slug', $params['brand_slug'])->value('id');
        }
        if (!empty($params['brand_id'])) {
            $q->where('brand_id', (int) $params['brand_id']);
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

            if (!empty($data['status'])) {
                if ($data['status'] === 'active') {
                    $data['is_active'] = true;
                    $data['published_at'] = $data['published_at'] ?? now();
                } else {
                    $data['is_active'] = false;
                    $data['published_at'] = null;
                }
            }

            $productData = Arr::only($data, self::PRODUCT_COLUMNS);
            $productData['currency'] = $productData['currency'] ?? "EUR";

            $product = Product::create($productData);

            //categories and audiences
            if (!empty($productData['primary_category_id'])) {
                $product->categories()->syncWithoutDetaching([$productData['primary_category_id']]);
            }
            if (is_array($categoryIds)) {
                $product->categories()->syncWithoutDetaching($categoryIds);
            }
            if (is_array($audienceIds)) {
                $product->audiences()->sync($audienceIds);
            }

            //options
            $optionIds = collect($data['options'] ?? [])
                ->pluck('option_id')->unique()->values()->all();
            if (!empty($optionIds)) {
                $product->options()->sync($optionIds); // product_options
            }

            //variants
            $variantsPayload = $data['variants'] ?? [];
            if (!empty($variantsPayload)) {

                // preload options (id, slug, position) & option values (id, option_id, slug)
                $options = Option::query()
                    ->whereIn('id', $optionIds ?: collect($variantsPayload)
                        ->flatMap(fn ($v) => collect($v['option_values'] ?? [])->pluck('option_id'))
                        ->unique()->values()->all())
                    ->select(['id', 'slug', 'position'])
                    ->get()
                    ->keyBy('id');

                $valueIds = collect($variantsPayload)
                    ->flatMap(fn ($v) => collect($v['option_values'] ?? [])->pluck('option_value_id'))
                    ->unique()->values()->all();

                $values = OptionValue::query()
                    ->whereIn('id', $valueIds)
                    ->select(['id', 'option_id', 'slug'])
                    ->get()
                    ->keyBy('id');

                // detect duplicates in payload AFTER building canonical keys
                $seenKeys = [];
                $pairsUnion = []; // for product_option_values sync later

                foreach ($variantsPayload as $variantRow) {
                    $pairs = collect($variantRow['option_values'] ?? [])->map(function ($p) use ($options, $values) {
                        $optId = (int) $p['option_id'];
                        $valId = (int) $p['option_value_id'];

                        if (!$options->has($optId)) {
                            throw new \RuntimeException("Option $optId not provided/active for this product.");
                        }
                        if (!$values->has($valId)) {
                            throw new \RuntimeException("Option value $valId not found.");
                        }

                        //value must belong to the same option
                        if ((int)$values[$valId]->option_id !== $optId) {
                            throw new \RuntimeException("Value $valId does not belong to option $optId.");
                        }

                        return [
                            'option_id' => $optId,
                            'option_slug' => (string)$options[$optId]->slug,
                            'option_position' => (int)$options[$optId]->position,
                            'option_value_id' => $valId,
                            'value_slug' => (string)$values[$valId]->slug,
                        ];
                    })->values();

                    //sort by option.position then option.slug for a canonical order
                    $sorted = $pairs->sortBy([
                        ['option_position', 'asc'],
                        ['option_slug', 'asc'],
                    ])->values();

                    $slugPairs = [];
                    foreach ($sorted as $item) {
                        $slugPairs[$item['option_slug']] = $item['value_slug'];
                    }
                    $variantKey = \App\Services\VariantKeyBuilder::makeFromSlugs($slugPairs);

                    if (isset($seenKeys[$variantKey])) {
                        throw new \RuntimeException("Duplicate variant combination: {$variantKey}");
                    }
                    $seenKeys[$variantKey] = true;

                    $variantRow['price_cents'] = isset($variantRow['price_cents']) && $variantRow['price_cents'] !== ''
                        ? (int) $variantRow['price_cents']
                        : null;

                    $variantRow['currency'] = isset($variantRow['currency']) && $variantRow['currency'] !== ''
                        ? (string) $variantRow['currency']
                        : null;

                    // both needed together
                    $hasPrice = !is_null($variantRow['price_cents']);
                    $hasCurr  = !is_null($variantRow['currency']);

                    if ($hasPrice xor $hasCurr) {
                        throw new \RuntimeException(
                            "Variant price override requires BOTH price_cents and currency, or NEITHER."
                        );
                    }

                    // create product_variant
                    /** @var ProductVariant $pv */
                    $pv = $product->variants()->create([
                        'variant_key' => $variantKey,
                        'sku' => $variantRow['sku'] ?? null,
                        'price_cents' => $variantRow['price_cents'] ?? null,
                        'currency' => $variantRow['currency'] ?? null,
                        'stock' => (int)($variantRow['stock'] ?? 0),
                        'is_active' => (bool)($variantRow['is_active'] ?? true),
                    ]);

                    // create product_variant_values
                    $pvValues = $sorted->map(fn ($item) => [
                        'product_variant_id' => $pv->id,
                        'option_id' => $item['option_id'],
                        'option_value_id' => $item['option_value_id'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])->all();

                    // bulk insert
                    ProductVariantValue::query()->insert($pvValues);

                    // accumulate union for product_option_values
                    foreach ($sorted as $item) {
                        $pairsUnion[$item['option_value_id']] = [
                            'option_id' => $item['option_id'],
                        ];
                    }
                }

                // sync product_option_values
                if (!empty($pairsUnion)) {
                    $product->selectedOptionValues()->sync($pairsUnion);
                }
            }

            $attributesPayload = $data['attributes'] ?? [];
            if (!empty($attributesPayload)) {
                $attach = [];
                foreach ($attributesPayload as $row) {
                    $attach[(int)$row['attribute_value_id']] = ['attribute_id' => (int)$row['attribute_id']];
                }
                $product->attributeValues()->sync($attach);
            }

            // Accept either URL or file (multipart) for each row
            $colorImages = $data['color_images'] ?? [];
            if (!empty($colorImages)) {
                foreach ($colorImages as $idx => $ci) {
                    $optionValueId = (int) $ci['option_value_id'];
                    $position = isset($ci['position']) ? (int)$ci['position'] : 0;

                    // ensure this option_value_id is allowed
                    $exists = DB::table('product_option_values')
                        ->where('product_id', $product->id)
                        ->where('option_value_id', $optionValueId)
                        ->exists();

                    if (!$exists) {
                        throw new \RuntimeException("Color image value {$optionValueId} is not allowed for this product.");
                    }

                    $imageUrl = $ci['url'] ?? null;

                    $uploadedFile = $files['color_images'][$idx]['file'] ?? null;
                    if ($uploadedFile) {
                        $path = $uploadedFile->store('product-color-images', 'public');
                        $imageUrl = Storage::disk('public')->url($path);
                    }

                    if (!$imageUrl) {
                        // if neither url nor file provided, skip this entry
                        continue;
                    }

                    ProductOptionValueImage::create([
                        'product_id' => $product->id,
                        'option_value_id' => $optionValueId,
                        'image_url' => $imageUrl,
                        'position' => $position,
                    ]);
                }
            }

            return $product->load([
                'brand',
                'primaryCategory',
                'categories',
                'audiences',
                'options',
                'selectedOptionValues',
                'variants.values.option',
                'variants.values.optionValue',
                'attributeValues',
                'colorImages',
            ]);
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

            return $product->load(['brand', 'primaryCategory', 'categories', 'audiences']);
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
