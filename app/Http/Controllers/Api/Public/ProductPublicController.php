<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductDetailResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Throwable;

final class ProductPublicController extends Controller
{
    public function show(Product $product): JsonResponse
    {
        try {
            $product->load([
                'primaryCategory.parent',
                'categories',
                'audiences',
                'options',
                'selectedOptionValues.option',
                'variants.values.option',
                'variants.product',
                'colorImages.optionValue.option',
                'attributeValues.attribute',
            ]);

            // breadcrumbs
            $breadcrumbs = [];
            $node = $product->primaryCategory;
            while ($node) {
                array_unshift($breadcrumbs, [
                    'id' => $node->id,
                    'name' => $node->name,
                    'slug' => $node->slug,
                    'path' => $node->path,
                ]);
                $node = $node->parent;
            }
            $product->setAttribute('breadcrumbs', $breadcrumbs);

            return (new ProductDetailResource($product))->response();
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Failed to fetch product detail'], 500);
        }
    }
}
