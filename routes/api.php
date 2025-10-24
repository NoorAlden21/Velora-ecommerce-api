<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Auth\AuthController;

// Public / Admin Catalog Controllers
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;

// Public storefront controllers
use App\Http\Controllers\Api\Public\ProductPublicController;
use App\Http\Controllers\Api\Public\ProductVariantPublicController;

// Admin product sub-resources
use App\Http\Controllers\Api\Admin\ProductOptionsController;
use App\Http\Controllers\Api\Admin\ProductVariantsController;
use App\Http\Controllers\Api\Admin\ProductColorImagesController;
use App\Http\Controllers\Api\Admin\ProductAttributesController;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Redirect;

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // demo/test protected endpoints
    Route::get('/admin-only', function () {
        return response()->json(['ok' => true, 'msg' => 'Hello Admin']);
    })->middleware('role:admin');

    Route::get('/orders/manage', function () {
        return response()->json(['ok' => true, 'msg' => 'You can manage orders']);
    })->middleware('permission:manage orders');
});

/*
|--------------------------------------------------------------------------
| Public Storefront
| (Slug-based binding: Product::getRouteKeyName() => 'slug', same for Category if enabled)
|--------------------------------------------------------------------------
*/

// Products (public)
Route::prefix('products')->group(function () {
    Route::get('', [ProductController::class, 'index']);
    Route::get('{product}', [ProductPublicController::class, 'show'])
        ->name('api.products.show')
        ->missing(function (Request $request) {
            $slug = $request->route('product');
            $hit = Redirect::where('model_type', Product::class)
                ->where('from_slug', $slug)
                ->latest('id')
                ->first();

            if ($hit) {
                return redirect()->route('api.products.show', ['product' => $hit->to_slug], 301);
            }

            abort(404);
        });

    Route::get('{product}/resolve-variant', [ProductVariantPublicController::class, 'resolve'])
        ->name('api.products.resolve')
        ->missing(function (Request $request) {
            $slug = $request->route('product');
            $hit = Redirect::where('model_type', Product::class)
                ->where('from_slug', $slug)
                ->latest('id')
                ->first();

            if ($hit) {
                // save the queries
                $qs = $request->getQueryString();
                $url = route('api.products.resolve', ['product' => $hit->to_slug]);
                return redirect($qs ? "{$url}?{$qs}" : $url, 301);
            }

            abort(404);
        });
});

// Categories (public)
Route::prefix('categories')->group(function () {
    Route::get('{category}',           [CategoryController::class, 'show']);
    Route::get('{category}/filters',   [CategoryController::class, 'filters']); // faceted filters for category
});

/*
|--------------------------------------------------------------------------
| Admin (Sanctum + Role:admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

    // Categories CRUD (admin)
    Route::get('/categories',           [CategoryController::class, 'index']);
    Route::post('/categories',           [CategoryController::class, 'store']);
    Route::put('/categories/{category:id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category:id}', [CategoryController::class, 'destroy']);

    // Products CRUD (admin)
    Route::post('/products',           [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    // Product sub-resources (admin)
    Route::post('/products/{product}/options',           [ProductOptionsController::class,   'store']);    // attach options + value_ids
    Route::post('/products/{product}/variants/generate', [ProductVariantsController::class,  'generate']); // cartesian + flags
    Route::post('/products/{product}/color-images',      [ProductColorImagesController::class, 'store']);   // per color value images
    Route::post('/products/{product}/attributes',        [ProductAttributesController::class, 'store']);    // assign attribute_value_ids
});
