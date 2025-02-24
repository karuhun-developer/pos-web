<?php

namespace App\Livewire\Cms\Pos\Product;

use App\Livewire\Forms\Cms\Pos\Product\FormMerk;
use App\Models\Pos\ProductMerk;
use BaseComponent;

class Merk extends BaseComponent
{
    public FormMerk $form;
    public $title = 'Product Merk';

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
        $isUpdate = false,
        $paginate = 10,
        $orderBy = 'name',
        $order = 'asc';

    public function render()
    {
        $get = $this->getDataWithFilter(
            model: new ProductMerk,
            searchBy: $this->searchBy,
            orderBy: $this->orderBy,
            order: $this->order,
            paginate: $this->paginate,
            s: $this->search
        );

        if ($this->search != null) {
            $this->resetPage();
        }

        return view('livewire.cms.pos.product.merk', compact('get'))->title($this->title);
    }
}
