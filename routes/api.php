<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);


    Route::get('/admin-only', function () {
        return response()->json(['ok' => true, 'msg' => 'Hello Admin']);
    })->middleware('role:admin');


    Route::get('/orders/manage', function () {
        return response()->json(['ok' => true, 'msg' => 'You can manage orders']);
    })->middleware('permission:manage orders');
});


// products  //

// Public (read)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
// If you prefer slug-based show:
// Route::get('/products/{product:slug}', [ProductController::class, 'show']);

// Admin (write)
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
});

//== products  ==//



// categories //

// Public reads
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::get('/categories/{category}/filters', [CategoryController::class, 'filters']);

// Admin CRUD
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
});


//===  categories ===//
