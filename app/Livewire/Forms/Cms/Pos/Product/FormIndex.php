<?php

namespace App\Livewire\Forms\Cms\Pos\Product;

use App\Enums\CommonStatusEnum;
use App\Livewire\Contracts\FormCrudInterface;
use App\Models\Pos\Product;
use App\Traits\WithMediaCollection;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Attributes\Validate;
use Livewire\Form;

class FormIndex extends Form implements FormCrudInterface
{
    use WithMediaCollection;

    #[Validate('nullable|numeric')]
    public $id;

    #[Validate('required|exists:App\Models\Pos\ProductCategory,id')]
    public $product_category_id;

    #[Validate('nullable|exists:App\Models\Pos\ProductSubCategory,id')]
    public $product_sub_category_id;

    #[Validate('nullable|exists:App\Models\Pos\ProductMerk,id')]
    public $product_merk_id;

    #[Validate('required')]
    public $sku;

    #[Validate('required')]
    public $name;

    #[Validate('required')]
    public $price;

    #[Validate('required')]
    public $stock;

    #[Validate('required')]
    public $description;

    #[Validate('required|in:' . CommonStatusEnum::ACTIVE->value . ',' . CommonStatusEnum::INACTIVE->value)]
    public $status;

    # Max 5MB
    #[Validate('nullable|image|mimes:jpeg,png,jpg,svg|max:5120')]
    public $image;

    public $old_data;

    // Get the data
    public function getDetail($id) {
        $data = Product::find($id);

        $this->id = $id;
        $this->old_data = $data;
        $this->product_category_id = $data->product_category_id;
        $this->product_sub_category_id = $data->product_sub_category_id;
        $this->product_merk_id = $data->product_merk_id;
        $this->sku = $data->sku;
        $this->name = $data->name;
        $this->price = $data->price;
        $this->stock = $data->stock;
        $this->description = $data->description;
        $this->status = $data->status->value;
    }

    // Save the data
    public function save() {
        $this->price = str_replace('.', '', $this->price);
        $this->stock = str_replace('.', '', $this->stock);
        $this->product_sub_category_id = $this->product_sub_category_id ?: null;
        $this->product_merk_id = $this->product_merk_id ?: null;

        $this->validate();

        if ($this->id) {
            $this->update();
        } else {
            $this->store();
        }

        $this->reset();
    }

    // Store data
    public function store() {
        $model = Product::create($this->all());

        $this->uploadFile($model);
    }

    // Update data
    public function update() {
        $model = Product::find($this->id);

        $this->uploadFile($model);

        $model->update($this->all());
    }

    // Delete data
    public function delete($id) {
        $model = Product::find($id);

        $this->deleteFile($model);

        $model->delete();
    }

    public function uploadFile(Product $model) {
        if($this->image instanceof TemporaryUploadedFile) {
            $this->saveFile(
                model: $model,
                file: $this->image,
            );
        }
    }
}
