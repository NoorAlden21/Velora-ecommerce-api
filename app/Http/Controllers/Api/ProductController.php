<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\ProductIndexRequest;
use App\Http\Requests\Products\ProductStoreRequest;
use App\Http\Requests\Products\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService)
    {
    }

    public function index(ProductIndexRequest $request): JsonResponse
    {
        try {
            $query = $this->productService->indexQuery($request->onlyFilters());
            $products = $query->paginate($request->perPage());
            return ProductResource::collection($products)->response();
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Failed to list products'], 500);
        }
    }

    public function store(ProductStoreRequest $request): JsonResponse
    {
        try {
            $product =  $this->productService->create($request->validated(), $request->allFiles());
            return (new ProductResource($product))->response()->setStatusCode(201);
        } catch (Throwable $e) {
            report($e);

            Log::error('Products@store: exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'trace_top' => collect($e->getTrace())->take(3)->all(),
            ]);

            if (config('app.debug')) {
                return response()->json([
                    'message' => 'Failed to create product',
                    'error' => $e->getMessage(),
                    'file' => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }

            return response()->json(['message' => 'Failed to create product'], 500);
        }
    }

    public function show(Product $product): JsonResponse
    {
        try {
            $product->load(['brand', 'primaryCategory', 'categories', 'audiences']);
            return (new ProductResource($product))->response();
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Failed to fetch product'], 500);
        }
    }

    public function update(ProductUpdateRequest $request, Product $product): JsonResponse
    {
        try {
            $product = $this->productService->update($product, $request->validated());
            return (new ProductResource($product))->response();
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Failed to update product'], 500);
        }
    }

    public function destroy(Product $product): JsonResponse
    {
        try {
            $this->productService->destroy($product);
            return response()->json(['status' => 'ok']);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Failed to delete product'], 500);
        }
    }
}
