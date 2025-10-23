<?php

namespace App\Services;

use App\Models\Option;
use App\Models\OptionValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductVariantService
{
    public function generateFromSelectedValues(Product $product, array $opts = []): Collection
    {
        return DB::transaction(function () use ($product, $opts) {
            $removeMissing      = (bool)($opts['remove_missing'] ?? false);
            $defaultStock       = (int)($opts['default_stock'] ?? 0);
            $defaultActive      = (bool)($opts['default_active'] ?? true);
            $defaultCurrency    = $opts['default_currency'] ?? $product->currency;
            $defaultPriceCents  = $opts['default_price_cents'] ?? $product->price_cents;

            $product->load(['options', 'selectedOptionValues.option']);
            $options = $product->options->sortBy('slug')->values();

            if ($options->isEmpty()) {
                return collect(); // no options -> no variants
            }

            // For each option, take selected values
            $selectedByOption = $product->selectedOptionValues
                ->groupBy(fn ($v) => $v->pivot->option_id)
                ->map(fn ($vals) => $vals->sortBy('slug')->values());

            // Build cartesian product of value sets
            $sets = [];
            foreach ($options as $opt) {
                $vals = $selectedByOption->get($opt->id, collect());
                if ($vals->isEmpty()) return collect(); // nothing to generate
                $sets[] = $vals;
            }

            $combinations = $this->cartesian($sets); // each item is array of OptionValue models (one per option)
            $generatedKeys = [];

            foreach ($combinations as $combo) {
                // Build [option_slug => value_slug]
                $pairs = [];
                foreach ($combo as $value) {
                    $optSlug = $value->option->slug;
                    $valSlug = $value->slug;
                    $pairs[$optSlug] = $valSlug;
                }
                $variantKey = VariantKeyBuilder::makeFromSlugs($pairs);
                $generatedKeys[] = $variantKey;

                // Create or keep existing variant
                /** @var ProductVariant $variant */
                $variant = ProductVariant::firstOrCreate(
                    ['product_id' => $product->id, 'variant_key' => $variantKey],
                    [
                        'sku'         => null,
                        'price_cents' => $defaultPriceCents,
                        'currency'    => $defaultCurrency,
                        'stock'       => $defaultStock,
                        'is_active'   => $defaultActive,
                    ]
                );

                // Ensure each option has one value row in product_variant_values
                foreach ($combo as $value) {
                    ProductVariantValue::updateOrCreate(
                        [
                            'product_variant_id' => $variant->id,
                            'option_id'          => $value->option_id,
                        ],
                        [
                            'option_value_id'    => $value->id,
                        ]
                    );
                }
            }

            // Remove missing variants if requested
            if ($removeMissing) {
                ProductVariant::where('product_id', $product->id)
                    ->whereNotIn('variant_key', $generatedKeys ?: ['__none__'])
                    ->delete();
            }

            // Return all current variants for this product (after generation)
            return ProductVariant::where('product_id', $product->id)
                ->with(['values.option', 'product'])
                ->get();
        });
    }

    /**
     * Cartesian product helper.
     * @param array<int, \Illuminate\Support\Collection> $sets
     * @return array<int, array<int, \App\Models\OptionValue>>
     */
    private function cartesian(array $sets): array
    {
        $result = [[]];
        foreach ($sets as $set) {
            $append = [];
            foreach ($result as $product) {
                foreach ($set as $item) {
                    $productCopy = $product;
                    $productCopy[] = $item;
                    $append[] = $productCopy;
                }
            }
            $result = $append;
        }
        return $result;
    }
}
