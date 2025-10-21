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
