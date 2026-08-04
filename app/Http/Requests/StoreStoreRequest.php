<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Buat outlet baru. */
class StoreStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
