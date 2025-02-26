<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth.api-key')->group(function () {
    // Welcome message
    Route::get('/', fn () => response()->json(['message' => 'Welcome to the API v1']), 200)->name('welcome');

    // Product Category
    Route::get('/product-category', [App\Http\Controllers\Api\V1\ProductCategoryController::class, 'index'])->name('product-category.index');
    Route::get('/product-category/{id}', [App\Http\Controllers\Api\V1\ProductCategoryController::class, 'show'])->name('product-category.show');
});
