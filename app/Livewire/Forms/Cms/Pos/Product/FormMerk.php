<?php

namespace App\Livewire\Forms\Cms\Pos\Product;

use App\Enums\CommonStatusEnum;
use App\Livewire\Contracts\FormCrudInterface;
use App\Models\Pos\ProductMerk;
use Livewire\Attributes\Validate;
use Livewire\Form;

class FormMerk extends Form implements FormCrudInterface
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
        $data = ProductMerk::find($id);

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
        ProductMerk::create($this->all());
    }

    // Update data
    public function update() {
        ProductMerk::find($this->id)->update($this->all());
    }

    // Delete data
    public function delete($id) {
        ProductMerk::find($id)->delete();
    }
}
