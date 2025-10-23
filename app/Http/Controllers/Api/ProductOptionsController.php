<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\ProductAttachOptionsRequest;
use App\Models\Product;
use App\Services\ProductOptionService;
use Illuminate\Http\JsonResponse;
use Throwable;

final class ProductOptionsController extends Controller
{
    public function __construct(private readonly ProductOptionService $service)
    {
    }

    public function store(Product $product, ProductAttachOptionsRequest $request): JsonResponse
    {
        try {
            $this->service->attachOptionsAndValues($product, $request->validated());
            return response()->json(['status' => 'ok']);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => $e->getMessage() ?: 'Failed to attach options'], 422);
        }
    }
}
