<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Categories\CategoryIndexRequest;
use App\Http\Requests\Categories\CategoryStoreRequest;
use App\Http\Requests\Categories\CategoryUpdateRequest;
use App\Http\Resources\AttributeResource;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Throwable;

final class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $service)
    {
    }

    public function index(CategoryIndexRequest $request): JsonResponse
    {
        try {
            $query = $this->service->indexQuery($request->validated());

            // sorting
            switch ($request->sort()) {
                case 'position_desc':
                    $query->orderByDesc('position');
                    break;
                case 'name_asc':
                    $query->orderBy('name');
                    break;
                case 'name_desc':
                    $query->orderByDesc('name');
                    break;
                case 'latest':
                    $query->latest('id');
                    break;
                default:
                    $query->orderBy('position')->orderBy('name');
                    break;
            }

            $cats = $query->paginate($request->perPage());
            return CategoryResource::collection($cats)->response();
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Failed to list categories'], 500);
        }
    }

    public function indexPublic(): JsonResponse
    {
        try {
            $cats = Category::with(['children' => fn ($q) => $q->orderBy('position')->orderBy('name')])
                ->whereNull('parent_id')
                ->orderBy('position')->orderBy('name')
                ->get();

            return CategoryResource::collection($cats)->response();
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Failed to list categories'], 500);
        }
    }

    public function store(CategoryStoreRequest $request): JsonResponse
    {
        try {
            $category = $this->service->create($request->validated());
            return (new CategoryResource($category))->response()->setStatusCode(201);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Failed to create category'], 500);
        }
    }

    public function show(Category $category): JsonResponse
    {
        try {
            return (new CategoryResource($category))->response();
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Failed to fetch category'], 500);
        }
    }

    public function update(CategoryUpdateRequest $request, Category $category): JsonResponse
    {
        try {
            $category = $this->service->update($category, $request->validated());
            return (new CategoryResource($category))->response();
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Failed to update category'], 500);
        }
    }

    public function destroy(Category $category): JsonResponse
    {
        try {
            $this->service->destroy($category);
            return response()->json(['status' => 'ok']);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => $e->getMessage() ?: 'Failed to delete category'], 422);
        }
    }

    public function filters(Category $category): JsonResponse
    {
        try {
            $category->load(['attributes.values']);
            return AttributeResource::collection($category->attributes)->response();
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Failed to load filters'], 500);
        }
    }
}
