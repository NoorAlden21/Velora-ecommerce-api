<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Support\Str;

class AttributesSeeder extends Seeder
{
    public function run(): void
    {
        $defs = [
            'material' => ['cotton', 'polyester', 'wool'],
            'fit'      => ['regular', 'slim', 'oversized', 'baggy'],
            'sleeve'   => ['none', 'short', 'long'],
            'brand'    => ['velora'],
        ];

        foreach ($defs as $attr => $values) {
            $a = Attribute::firstOrCreate(['slug' => Str::slug($attr)], ['name' => $attr]);
            foreach ($values as $pos => $v) {
                AttributeValue::firstOrCreate(
                    ['attribute_id' => $a->id, 'slug' => Str::slug($v)],
                    ['value' => $v, 'position' => $pos]
                );
            }
        }
    }
}
