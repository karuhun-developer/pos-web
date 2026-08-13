<?php

namespace App\Http\Requests\Web;

use App\Models\Donation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DonationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(Donation::STATUSES)],
        ];
    }
}
