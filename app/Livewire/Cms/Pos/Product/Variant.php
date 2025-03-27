<?php

namespace App\Livewire\Cms\Pos\Product;

use App\Enums\CommonStatusEnum;
use App\Livewire\Forms\Cms\Pos\Product\FormVariant;
use App\Models\Pos\Product;
use App\Models\Pos\ProductVariant;
use Livewire\WithFileUploads;
use BaseComponent;

class Variant extends BaseComponent
{
    use WithFileUploads;

    public FormVariant $form;
    public $title = 'Product Variant';

    public $searchBy = [
            [
                'name' => 'SKU',
                'field' => 'sku',
            ],
            [
                'name' => 'Name',
                'field' => 'name',
            ],
            [
                'name' => 'Price',
                'field' => 'price',
            ],
            [
                'name' => 'Stock',
                'field' => 'stock',
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
        $orderBy = 'sku',
        $order = 'asc';

    public $product;

    public function mount($productId) {
        $this->product = Product::where('status', CommonStatusEnum::ACTIVE->value)->find($productId);

        // If the product is not found, redirect to the product index page
        if(!$this->product) {
            return $this->redirectRoute('cms.product.index', navigate: true);
        }

        $this->title .= ' [Product : ' . $this->product->name . ']';
    }

    public function render()
    {
        $get = $this->getDataWithFilter(
            model: new ProductVariant,
            searchBy: $this->searchBy,
            orderBy: $this->orderBy,
            order: $this->order,
            paginate: $this->paginate,
            s: $this->search
        );

        if ($this->search != null) {
            $this->resetPage();
        }

        return view('livewire.cms.pos.product.variant', compact('get'))->title($this->title);
    }

    public function customSave() {
        $this->form->product_id = $this->product->id;
        $this->save();
    }
}
