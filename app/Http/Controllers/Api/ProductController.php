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
use Throwable;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService)
    {
    }

    public function index(ProductIndexRequest $request): JsonResponse
    {
        try {
            $query = Product::query()->with(['primaryCategory', 'categories', 'audiences'])
                ->where('is_active', true);

            if ($cid = $request->input('category_id')) {
                $query->where('primary_category_id', $cid);
            }

            if ($q = $request->input('q')) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");
                });
            }

            // AND across attributes, OR within attribute
            foreach ($request->filters() as $attributeId => $valueIds) {
                if (!is_array($valueIds) || empty($valueIds)) continue;
                $query->whereHas('attributeValues', function ($qh) use ($attributeId, $valueIds) {
                    $qh->where('product_attribute_values.attribute_id', $attributeId)
                        ->whereIn('product_attribute_values.attribute_value_id', $valueIds);
                });
            }

            // Sorting
            switch ($request->sort()) {
                case 'price_asc':
                    $query->orderBy('price_cents');
                    break;
                case 'price_desc':
                    $query->orderByDesc('price_cents');
                    break;
                case 'name_asc':
                    $query->orderBy('name');
                    break;
                case 'name_desc':
                    $query->orderByDesc('name');
                    break;
                default:
                    $query->latest('published_at');
                    break;
            }

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
            $product = $this->productService->create($request->validated());
            return (new ProductResource($product))->response()->setStatusCode(201);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Failed to create product'], 500);
        }
    }

    public function show(Product $product): JsonResponse
    {
        try {
            $product->load(['primaryCategory', 'categories', 'audiences']);
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
