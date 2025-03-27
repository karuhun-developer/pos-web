<?php

namespace App\Livewire\Cms\Pos\Product;

use App\Livewire\Forms\Cms\Pos\Product\FormCategory;
use App\Models\Pos\ProductCategory;
use BaseComponent;

class Category extends BaseComponent
{
    public FormCategory $form;
    public $title = 'Product Category';

    public $searchBy = [
            [
                'name' => 'Name',
                'field' => 'name',
            ],
            [
                'name' => 'Description',
                'field' => 'description',
            ],
            [
                'name' => 'Status',
                'field' => 'status',
            ],
        ],
        $search = '',
        $paginate = 10,
        $orderBy = 'name',
        $order = 'asc';

    public function render()
    {
        $get = $this->getDataWithFilter(
            model: new ProductCategory,
            searchBy: $this->searchBy,
            orderBy: $this->orderBy,
            order: $this->order,
            paginate: $this->paginate,
            s: $this->search
        );

        if ($this->search != null) {
            $this->resetPage();
        }

        return view('livewire.cms.pos.product.category', compact('get'))->title($this->title);
    }
}
