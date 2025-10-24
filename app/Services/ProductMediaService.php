<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductOptionValueImage;
use Illuminate\Support\Facades\DB;

class ProductMediaService
{
    public function saveColorImages(Product $product, array $payload): void
    {
        DB::transaction(function () use ($product, $payload) {
            foreach ($payload['items'] as $item) {
                $optionValueId = $item['option_value_id'];
                $files = $item['files'];

                // delete then insert ordered
                ProductOptionValueImage::where('product_id', $product->id)
                    ->where('option_value_id', $optionValueId)
                    ->delete();

                $rows = [];
                foreach ($files as $file) {
                    $rows[] = [
                        'product_id'      => $product->id,
                        'option_value_id' => $optionValueId,
                        'image_url'       => $file['url'],
                        'position'        => $file['position'] ?? 0,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                }

                if (!empty($rows)) {
                    DB::table('product_option_value_images')->insert($rows);
                }
            }
        });
    }
}
