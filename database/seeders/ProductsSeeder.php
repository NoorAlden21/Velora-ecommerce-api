<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\ProductService;
use App\Models\Category;
use App\Models\Audience;

class ProductsSeeder extends Seeder
{
    public function __construct(private readonly ProductService $service)
    {
    }

    public function run(): void
    {
        $menTopsId   = Category::where('path', 'men/tops')->value('id');
        $womenTopsId = Category::where('path', 'women/tops')->value('id');
        $kidsTopsId  = Category::where('path', 'kids/tops')->value('id');

        $audMen   = Audience::where('slug', 'men')->value('id');
        $audWomen = Audience::where('slug', 'women')->value('id');
        $audKids  = Audience::where('slug', 'kids')->value('id');

        $this->service->create([
            'name' => 'Essential Tee',
            'price_cents' => 1999,
            'currency' => 'EUR',
            'primary_category_id' => $menTopsId,
            'audience_ids' => [$audMen],
            'description' => '100% cotton tee.',
            'meta_title' => 'Essential Tee',
            'meta_description' => 'Classic cotton tee for everyday wear.',
        ]);

        $this->service->create([
            'name' => 'Everyday Tee',
            'price_cents' => 1899,
            'currency' => 'EUR',
            'primary_category_id' => $womenTopsId,
            'audience_ids' => [$audWomen],
            'description' => 'Soft everyday tee.',
            'meta_title' => 'Everyday Tee',
            'meta_description' => 'Soft, comfy tee.',
        ]);

        $this->service->create([
            'name' => 'Kids Graphic Tee',
            'price_cents' => 1499,
            'currency' => 'EUR',
            'primary_category_id' => $kidsTopsId,
            'audience_ids' => [$audKids],
            'description' => 'Fun graphic tee for kids.',
            'meta_title' => 'Kids Graphic Tee',
            'meta_description' => 'Colorful prints kids love.',
        ]);
    }
}
