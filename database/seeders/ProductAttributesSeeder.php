<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Attribute;
use App\Models\AttributeValue;

class ProductAttributesSeeder extends Seeder
{
    public function run(): void
    {
        $materialId = Attribute::where('slug', 'material')->value('id');
        $fitId      = Attribute::where('slug', 'fit')->value('id');
        $sleeveId   = Attribute::where('slug', 'sleeve')->value('id');
        $brandId    = Attribute::where('slug', 'brand')->value('id');

        if (!$materialId || !$fitId || !$sleeveId || !$brandId) {
            $this->command->warn('Attributes missing; skipping ProductAttributesSeeder.');
            return;
        }

        $cotton  = AttributeValue::where('attribute_id', $materialId)->where('slug', 'cotton')->first();
        $regular = AttributeValue::where('attribute_id', $fitId)->where('slug', 'regular')->first();
        $short   = AttributeValue::where('attribute_id', $sleeveId)->where('slug', 'short')->first();
        $velora  = AttributeValue::where('attribute_id', $brandId)->where('slug', 'velora')->first();

        // Filter nulls out
        $values = collect([$cotton, $regular, $short, $velora])->filter();

        if ($values->isEmpty()) {
            $this->command->warn('No AttributeValues found; skipping ProductAttributesSeeder.');
            return;
        }

        // attribute_value_id => ['attribute_id' => ...]
        $pivotMap = $values
            ->mapWithKeys(fn ($av) => [$av->id => ['attribute_id' => $av->attribute_id]])
            ->toArray();

        foreach (Product::all() as $p) {
            $p->attributeValues()->syncWithoutDetaching($pivotMap);
        }
    }
}
