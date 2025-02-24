<?php

use Illuminate\Support\Facades\Route;

// Product Category
Route::get('/product/category', App\Livewire\Cms\Pos\Product\Category::class)->name('product.category');
// Product Sub Category
Route::get('/product/sub-category', App\Livewire\Cms\Pos\Product\SubCategory::class)->name('product.sub-category');
// Product Merk
Route::get('/product/merk', App\Livewire\Cms\Pos\Product\Merk::class)->name('product.merk');
