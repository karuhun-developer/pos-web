<?php

use Illuminate\Support\Facades\Route;

// Pos Category
Route::get('/product/category', App\Livewire\Cms\Pos\Product\Category::class)->name('product.category');
