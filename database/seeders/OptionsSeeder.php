<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Option;
use App\Models\OptionValue;
use Illuminate\Support\Str;

class OptionsSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            [
                'name'   => 'color',
                'slug'   => 'color',
                'type'   => 'color', // matches your options table "type"
                'values' => [
                    ['name' => 'red',   'slug' => 'red',   'code' => '#FF0000'],
                    ['name' => 'black', 'slug' => 'black', 'code' => '#000000'],
                    ['name' => 'white', 'slug' => 'white', 'code' => '#FFFFFF'],
                    ['name' => 'blue',  'slug' => 'blue',  'code' => '#0000FF'],
                    ['name' => 'green', 'slug' => 'green', 'code' => '#008000'],
                ],
            ],
            [
                'name'   => 'size',
                'slug'   => 'size',
                'type'   => 'size',
                'values' => [
                    ['name' => 'XS', 'slug' => Str::slug('XS')], // xs
                    ['name' => 'S',  'slug' => Str::slug('S')],  // s
                    ['name' => 'M',  'slug' => Str::slug('M')],  // m
                    ['name' => 'L',  'slug' => Str::slug('L')],  // l
                    ['name' => 'XL', 'slug' => Str::slug('XL')], // xl
                ],
            ],
        ];

        foreach ($definitions as $def) {
            $option = Option::updateOrCreate(
                ['slug' => $def['slug']],
                [
                    'name'       => $def['name'],
                    'type'       => $def['type'] ?? 'text',
                    'is_active'  => true,
                    'position'   => 0,
                ]
            );

            foreach ($def['values'] as $pos => $v) {
                OptionValue::updateOrCreate(
                    [
                        'option_id' => $option->id,
                        'slug'      => $v['slug'] ?? Str::slug($v['name']),
                    ],
                    [
                        'name'     => $v['name'],
                        'code'     => $v['code'] ?? null,
                        'position' => $pos,
                    ]
                );
            }
        }
    }
}
