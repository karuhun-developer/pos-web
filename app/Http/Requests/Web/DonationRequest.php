<?php

namespace App\Http\Requests\Web;

use App\Support\DonationSettings;
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
            // Kanal yang belum dikonfigurasi tidak boleh dipilih sekalipun
            // formulirnya diakali — "sudah transfer ke QRIS" tidak masuk akal
            // kalau QRIS-nya memang tidak pernah ada.
            'channel' => ['required', Rule::in(DonationSettings::channels())],
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
            'channel' => 'cara pembayaran',
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
