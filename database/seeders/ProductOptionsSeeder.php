<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Option;
use App\Models\OptionValue;

class ProductOptionsSeeder extends Seeder
{
    public function run(): void
    {
        $color = Option::where('slug', 'color')->first();
        $size  = Option::where('slug', 'size')->first();

        if (!$color || !$size) return;

        $colorIds = OptionValue::where('option_id', $color->id)
            ->whereIn('slug', ['red', 'black', 'white'])
            ->pluck('id')->all();

        $sizeIds = OptionValue::where('option_id', $size->id)
            ->pluck('id')->all();

        foreach (Product::all() as $p) {
            // option_value_id => ['option_id' => X]
            $colorMap = collect($colorIds)
                ->mapWithKeys(fn ($id) => [$id => ['option_id' => $color->id]])
                ->toArray();

            $sizeMap = collect($sizeIds)
                ->mapWithKeys(fn ($id) => [$id => ['option_id' => $size->id]])
                ->toArray();

            // including pivot data
            $p->selectedOptionValues()->syncWithoutDetaching($colorMap);
            $p->selectedOptionValues()->syncWithoutDetaching($sizeMap);
        }
    }
}
