<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Pos\Product;
use App\Traits\WithGetFilterDataApi;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use WithGetFilterDataApi;

    // Get all data
    public function index(Request $request) {
        $model = Product::query()
            ->with('category', 'subCategory', 'merk', 'media')
            ->join('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->leftJoin('product_sub_categories', 'products.product_sub_category_id', '=', 'product_sub_categories.id')
            ->leftJoin('product_merks', 'products.product_merk_id', '=', 'product_merks.id')
            ->select('products.*');

        // Check if has product_category_id
        if($request?->product_category_id) {
            $model->where(function($query) use ($request) {
                foreach($request->product_category_id as $categoryId) {
                    $query->orWhere('products.product_category_id', $categoryId);
                }
            });
        }

        // Check if has product_sub_category_id
        if($request?->product_sub_category_id) {
            $model->where(function($query) use ($request) {
                foreach($request->product_sub_category_id as $subCategoryId) {
                    $query->orWhere('products.product_sub_category_id', $subCategoryId);
                }
            });
        }

        // Check if has product_merk_id
        if($request?->product_merk_id) {
            $model->where(function($query) use ($request) {
                foreach($request->product_merk_id as $merkId) {
                    $query->orWhere('products.product_merk_id', $merkId);
                }
            });
        }

        $data = $this->getDataWithFilter(
            model: $model,
            searchBy: [
                'products.sku',
                'products.name',
                'products.price',
                'products.stock',
                'products.description',
                'products.status',
                'product_categories.name',
                'product_sub_categories.name',
                'product_merks.name',
            ],
            orderBy: $request?->orderBy ?? 'products.id',
            order: $request?->order ?? 'asc',
            paginate: $request?->paginate ?? 10,
            searchBySpecific: $request?->searchBySpecific ?? '',
            s: $request?->search ?? '',
        );

        return $this->responseWithSuccess($data);
    }

    // Get detail data
    public function show($id) {
        $data = Product::with('category', 'subCategory', 'merk', 'media', 'variants', 'variants.media')->find($id);

        // If the data is not found
        if(!$data) return $this->responseNotFound();

        return $this->responseWithSuccess($data);
    }
}
