<?php

namespace App\Livewire\Cms\Pos\Product;

use App\Enums\CommonStatusEnum;
use App\Livewire\Forms\Cms\Pos\Product\FormSubCategory;
use App\Models\Pos\ProductCategory;
use App\Models\Pos\ProductSubCategory;
use BaseComponent;

class SubCategory extends BaseComponent
{
    public FormSubCategory $form;
    public $title = 'Product Sub Category';

    public $searchBy = [
            [
                'name' => 'Category',
                'field' => 'product_categories.name',
            ],
            [
                'name' => 'Name',
                'field' => 'product_sub_categories.name',
            ],
            [
                'name' => 'Description',
                'field' => 'product_sub_categories.description',
            ],
            [
                'name' => 'Status',
                'field' => 'product_sub_categories.status',
            ],
        ],
        $search = '',
        $isUpdate = false,
        $paginate = 10,
        $orderBy = 'product_sub_categories.name',
        $order = 'asc';

    public $categories = [];

    public function mount() {
        $this->categories = ProductCategory::where('status', CommonStatusEnum::ACTIVE->value)->get();
    }

    public function render()
    {
        $model = ProductSubCategory::query()
            ->join('product_categories', 'product_categories.id', '=', 'product_sub_categories.product_category_id')
            ->select('product_sub_categories.*', 'product_categories.name as category_name');

        $get = $this->getDataWithFilter(
            model: $model,
            searchBy: $this->searchBy,
            orderBy: $this->orderBy,
            order: $this->order,
            paginate: $this->paginate,
            s: $this->search
        );

        if ($this->search != null) {
            $this->resetPage();
        }

        return view('livewire.cms.pos.product.sub-category', compact('get'))->title($this->title);
    }
}
