<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;


//have a relook on ProductVariant and ProductAttributesSeeder

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndAdminSeeder::class,

            AudienceSeeder::class,
            OptionsSeeder::class,
            AttributesSeeder::class,
            CategoriesSeeder::class,

            ProductsSeeder::class,
            ProductOptionsSeeder::class,
            ProductVariantsSeeder::class,
            ProductColorImagesSeeder::class,
            ProductAttributesSeeder::class,
        ]);
    }
}
