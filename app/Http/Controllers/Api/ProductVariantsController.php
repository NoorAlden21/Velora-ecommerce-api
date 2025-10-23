<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\ProductGenerateVariantsRequest;
use App\Http\Resources\VariantResource;
use App\Models\Product;
use App\Services\ProductVariantService;
use Illuminate\Http\JsonResponse;
use Throwable;

final class ProductVariantsController extends Controller
{
    public function __construct(private readonly ProductVariantService $service)
    {
    }

    public function generate(Product $product, ProductGenerateVariantsRequest $request): JsonResponse
    {
        try {
            $variants = $this->service->generateFromSelectedValues($product, $request->validated());
            return VariantResource::collection($variants)->response();
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Failed to generate variants'], 500);
        }
    }
}
