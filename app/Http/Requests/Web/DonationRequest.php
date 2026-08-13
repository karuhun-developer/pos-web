<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'donor_name' => ['required', 'string', 'max:80'],
            'donor_email' => ['nullable', 'email', 'max:120'],
            'amount' => [
                'required', 'integer',
                'min:'.(int) config('donation.min'),
                'max:'.(int) config('donation.max'),
            ],
            'message' => ['nullable', 'string', 'max:300'],
            // `external` tidak pernah dikirim ke sini — kanal itu cuma tautan
            // keluar, tidak ada yang bisa dicatat dari sisi kami.
            'channel' => ['required', Rule::in(['manual'])],
            'is_anonymous' => ['boolean'],
        ];
    }

    /** @return array<string,string> */
    public function attributes(): array
    {
        return [
            'donor_name' => 'nama',
            'donor_email' => 'email',
            'amount' => 'nominal',
            'message' => 'pesan',
            'channel' => 'metode',
        ];
    }

    /** @return array<string,string> */
    public function messages(): array
    {
        return [
            'amount.min' => 'Nominal minimal Rp'.number_format((int) config('donation.min'), 0, ',', '.').'.',
        ];
    }
}
