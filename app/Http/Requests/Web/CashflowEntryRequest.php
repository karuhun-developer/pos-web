<?php

namespace App\Http\Requests\Web;

use App\Support\StoreContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashflowEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'category_id' => [
                'nullable', 'uuid',
                Rule::exists('cashflow_categories', 'id')->where(
                    fn ($query) => $query->where('store_id', StoreContext::id())->whereNull('deleted_at'),
                ),
            ],
            'type' => ['required_without:category_id', Rule::in(['income', 'expense'])],
            'amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'note' => ['nullable', 'string', 'max:500'],
            // Form mengirim tanggal-waktu lokal; controller yang mengubahnya
            // jadi epoch ms (occurred_at) sesuai zona tampilan.
            'occurred_on' => ['required', 'date'],
        ];
    }

    /** @return array<string,string> */
    public function attributes(): array
    {
        return [
            'category_id' => 'kategori',
            'type' => 'tipe',
            'amount' => 'nominal',
            'note' => 'catatan',
            'occurred_on' => 'tanggal',
        ];
    }
}
