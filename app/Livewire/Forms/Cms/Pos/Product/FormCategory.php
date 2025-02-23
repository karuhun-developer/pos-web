<?php

namespace App\Livewire\Forms\Cms\Pos\Product;

use App\Enums\CommonStatusEnum;
use App\Livewire\Contracts\FormCrudInterface;
use App\Models\Pos\ProductCategory;
use Livewire\Attributes\Validate;
use Livewire\Form;

class FormCategory extends Form implements FormCrudInterface
{
    #[Validate('nullable|numeric')]
    public $id;

    #[Validate('required')]
    public $name;

    #[Validate('required')]
    public $description;

    #[Validate('required|in:' . CommonStatusEnum::ACTIVE->value . ',' . CommonStatusEnum::INACTIVE->value)]
    public $status;

    // Get the data
    public function getDetail($id) {
        $data = ProductCategory::find($id);

        $this->id = $id;
        $this->name = $data->name;
        $this->description = $data->description;
        $this->status = $data->status->value;
    }

    // Save the data
    public function save() {
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
        ProductCategory::create($this->all());
    }

    // Update data
    public function update() {
        ProductCategory::find($this->id)->update($this->all());
    }

    // Delete data
    public function delete($id) {
        ProductCategory::find($id)->delete();
    }
}
