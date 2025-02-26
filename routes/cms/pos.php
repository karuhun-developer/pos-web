<?php

use Illuminate\Support\Facades\Route;

// Product Category
Route::get('/product/category', App\Livewire\Cms\Pos\Product\Category::class)->name('product.category');
// Product Sub Category
Route::get('/product/sub-category', App\Livewire\Cms\Pos\Product\SubCategory::class)->name('product.sub-category');
// Product Merk
Route::get('/product/merk', App\Livewire\Cms\Pos\Product\Merk::class)->name('product.merk');
// Product List
Route::get('/product/index', App\Livewire\Cms\Pos\Product\Index::class)->name('product.index');
// Product Variant
Route::get('/product/{productId}/variant', App\Livewire\Cms\Pos\Product\Variant::class)->name('product.variant');

