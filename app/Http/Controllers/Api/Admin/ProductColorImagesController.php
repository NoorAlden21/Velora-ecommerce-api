<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\ProductColorImagesRequest;
use App\Models\Product;
use App\Services\ProductMediaService;
use Illuminate\Http\JsonResponse;
use Throwable;

final class ProductColorImagesController extends Controller
{
    public function __construct(private readonly ProductMediaService $service)
    {
    }

    public function store(Product $product, ProductColorImagesRequest $request): JsonResponse
    {
        try {
            $this->service->saveColorImages($product, $request->validated());
            return response()->json(['status' => 'ok']);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Failed to save color images'], 500);
        }
    }
}
