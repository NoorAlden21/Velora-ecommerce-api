<?php

namespace Database\Seeders;

use App\Models\Audience;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AudienceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['name' => 'Men',   'slug' => 'men',   'is_active' => true],
            ['name' => 'Women', 'slug' => 'women', 'is_active' => true],
            ['name' => 'Kids',  'slug' => 'kids',  'is_active' => true],
        ];

        foreach ($data as $row) {
            Audience::updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
