<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Brand;

class BrandsSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            [
                'name' => 'Velora',
                'website_url' => 'https://example.com/velora',
                'description' => 'Velora house line.',
            ],
            [
                'name' => 'Northwind',
                'website_url' => 'https://example.com/northwind',
                'description' => 'Outdoor & essentials.',
            ],
            [
                'name' => 'Acme',
                'website_url' => 'https://example.com/acme',
                'description' => 'Everyday basics.',
            ],
        ];

        foreach ($brands as $b) {
            $slug = Str::slug($b['name']);
            Brand::firstOrCreate(
                ['slug' => $slug],
                [
                    'name'             => $b['name'],
                    'is_active'        => true,
                    'logo_path'        => null,
                    'website_url'      => $b['website_url'] ?? null,
                    'description'      => $b['description'] ?? null,
                    'meta_title'       => $b['name'],
                    'meta_description' => $b['description'] ?? null,
                ]
            );
        }
    }
}
