<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\ProductAssignAttributesRequest;
use App\Models\Product;
use App\Services\ProductAttributeService;
use Illuminate\Http\JsonResponse;
use Throwable;

final class ProductAttributesController extends Controller
{
    public function __construct(private readonly ProductAttributeService $service)
    {
    }

    public function store(Product $product, ProductAssignAttributesRequest $request): JsonResponse
    {
        try {
            $this->service->assignAttributeValues($product, $request->validated());
            return response()->json(['status' => 'ok']);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Failed to assign attributes'], 500);
        }
    }
}
