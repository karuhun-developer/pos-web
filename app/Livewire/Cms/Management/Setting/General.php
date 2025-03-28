<?php

namespace App\Livewire\Cms\Management\Setting;

use App\Livewire\Forms\Cms\Management\Setting\FormGeneral;
use Livewire\WithFileUploads;
use BaseComponent;

class General extends BaseComponent
{
    use WithFileUploads;

    public FormGeneral $form;
    public $title = 'Setting';

    public function mount() {
        $this->isUpdate = true;
        $this->form->getData();
    }

    public function render()
    {
        return view('livewire.cms.management.setting.general')->title($this->title);
    }
}
