<?php

namespace App\Http\Requests\Web;

use App\Models\Donation;
use Illuminate\Foundation\Http\FormRequest;

class DonationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Donation::class) ?? false;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            // 2 MB sudah kelewat besar untuk sebuah QR; batasnya ada supaya
            // superadmin tidak sengaja menaruh foto kamera 8 MP di halaman
            // publik yang dibuka orang lewat data seluler.
            'qris' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_qris' => ['boolean'],

            'banks' => ['array', 'max:5'],
            'banks.*.bank' => ['nullable', 'string', 'max:40'],
            'banks.*.account_number' => ['nullable', 'string', 'max:40'],
            'banks.*.account_name' => ['nullable', 'string', 'max:80'],

            'saweria_url' => ['nullable', 'url', 'max:200'],
            'note' => ['nullable', 'string', 'max:300'],
        ];
    }

    /** @return array<string,string> */
    public function attributes(): array
    {
        return [
            'qris' => 'gambar QRIS',
            'saweria_url' => 'tautan Saweria',
            'note' => 'catatan',
        ];
    }
}
