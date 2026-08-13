<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
        ];
    }

    /** @return array<string,string> */
    public function attributes(): array
    {
        return ['name' => 'nama toko'];
    }
}
