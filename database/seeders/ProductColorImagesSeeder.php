<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Option;
use App\Models\OptionValue;
use App\Models\ProductOptionValueImage;

class ProductColorImagesSeeder extends Seeder
{
    public function run(): void
    {
        $colorOpt = Option::where('slug', 'color')->first();

        $colors = OptionValue::where('option_id', $colorOpt->id)
            ->whereIn('slug', ['red', 'black', 'white'])
            ->get();

        foreach (Product::all() as $p) {
            foreach ($colors as $c) {
                foreach ([1, 2] as $i) {
                    ProductOptionValueImage::firstOrCreate(
                        [
                            'product_id' => $p->id,
                            'option_value_id' => $c->id,
                            'position' => $i - 1,
                        ],
                        [
                            'image_url' => "https://picsum.photos/seed/{$p->id}-{$c->slug}-{$i}/800/800",
                        ]
                    );
                }
            }
        }
    }
}
