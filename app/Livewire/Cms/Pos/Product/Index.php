<?php

namespace App\Livewire\Cms\Pos\Product;

use App\Enums\CommonStatusEnum;
use App\Livewire\Forms\Cms\Pos\Product\FormIndex;
use App\Models\Pos\Product;
use App\Models\Pos\ProductCategory;
use App\Models\Pos\ProductMerk;
use App\Models\Pos\ProductSubCategory;
use Livewire\WithFileUploads;
use BaseComponent;

class Index extends BaseComponent
{
    use WithFileUploads;

    public FormIndex $form;
    public $title = 'Product List';

    public $searchBy = [
            [
                'name' => 'Category',
                'field' => 'product_categories.name',
            ],
            [
                'name' => 'Sub Category',
                'field' => 'product_sub_categories.name',
            ],
            [
                'name' => 'Merk',
                'field' => 'product_merks.name',
            ],
            [
                'name' => 'SKU',
                'field' => 'products.sku',
            ],
            [
                'name' => 'Name',
                'field' => 'products.name',
            ],
            [
                'name' => 'Price',
                'field' => 'products.price',
            ],
            [
                'name' => 'Stock',
                'field' => 'products.stock',
            ],
            [
                'name' => 'Description',
                'field' => 'products.description',
            ],
            [
                'name' => 'Status',
                'field' => 'products.status',
            ],
        ],
        $search = '',
        $paginate = 10,
        $orderBy = 'products.sku',
        $order = 'asc';

    public $categories = [];
    public $subCategories = [];
    public $merks = [];

    public function mount() {
        $this->categories = ProductCategory::where('status', CommonStatusEnum::ACTIVE->value)->get();
        $this->merks = ProductMerk::where('status', CommonStatusEnum::ACTIVE->value)->get();
    }

    public function getSubCategories() {
        $this->subCategories = ProductSubCategory::where([
            'status' => CommonStatusEnum::ACTIVE->value,
            'product_category_id' => $this->form->product_category_id,
        ])->get();
    }

    public function render()
    {
        $model = Product::query()
            ->join('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->leftJoin('product_sub_categories', 'products.product_sub_category_id', '=', 'product_sub_categories.id')
            ->leftJoin('product_merks', 'products.product_merk_id', '=', 'product_merks.id')
            ->select(
                'products.*',
                'product_categories.name as category_name',
                'product_sub_categories.name as sub_category_name',
                'product_merks.name as merk_name'
            );

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

        return view('livewire.cms.pos.product.index', compact('get'))->title($this->title);
    }

    public function customEdit($id) {
        $this->edit($id);
        $this->getSubCategories();
        $this->dispatch('setValueById', id: 'product_sub_category_id', value: $this->form->product_sub_category_id);
    }
}
