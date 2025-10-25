<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Option;
use App\Models\OptionValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Demo cartesian generator (color x size) based on selectedOptionValues.
 * If your project already has a VariantsService::generate(), feel free to replace this logic with that service call.
 */
class ProductVariantsSeeder extends Seeder
{
    public function run(): void
    {
        $colorOpt = Option::where('slug', 'color')->first();
        $sizeOpt  = Option::where('slug', 'size')->first();

        if (!$colorOpt || !$sizeOpt) return;

        foreach (Product::with('selectedOptionValues')->get() as $product) {
            $sel = $product->selectedOptionValues()->get()->groupBy('option_id');

            $colors = ($sel[$colorOpt->id] ?? collect())->values();
            $sizes  = ($sel[$sizeOpt->id]  ?? collect())->values();

            if ($colors->isEmpty() || $sizes->isEmpty()) {
                continue;
            }

            DB::transaction(function () use ($product, $colors, $sizes) {
                foreach ($colors as $c) {
                    foreach ($sizes as $s) {
                        $key = "color:{$c->slug}|size:{$s->slug}";

                        $variant = ProductVariant::firstOrCreate(
                            ['product_id' => $product->id, 'variant_key' => $key],
                            [
                                'sku'         => strtoupper(Str::slug($product->slug . '-' . $c->slug . '-' . $s->slug)),
                                'price_cents' => $product->price_cents,
                                'stock'       => 25,
                                'is_active'   => true,
                            ]
                        );

                        // attach value links
                        ProductVariantValue::firstOrCreate([
                            'product_variant_id' => $variant->id,
                            'option_id'          => $c->option_id,
                            'option_value_id'    => $c->id,
                        ]);
                        ProductVariantValue::firstOrCreate([
                            'product_variant_id' => $variant->id,
                            'option_id'          => $s->option_id,
                            'option_value_id'    => $s->id,
                        ]);
                    }
                }
            });
        }
    }
}
