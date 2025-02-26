<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Pos\ProductCategory;
use App\Traits\WithGetFilterDataApi;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    use WithGetFilterDataApi;

    // Get all data
    public function index(Request $request) {
        $data = $this->getDataWithFilter(
            model: new ProductCategory,
            searchBy: [
                'name',
                'description',
                'status',
            ],
            orderBy: $request?->orderBy ?? 'id',
            order: $request?->order ?? 'asc',
            paginate: $request?->paginate ?? 10,
            searchBySpecific: $request?->searchBySpecific ?? '',
            s: $request?->search ?? '',
        );

        return $this->responseWithSuccess($data);
    }

    // Get detail data
    public function show($id) {
        $data = ProductCategory::with('subCategories')->find($id);

        // If the data is not found
        if(!$data) return $this->responseNotFound();

        return $this->responseWithSuccess($data);
    }
}
