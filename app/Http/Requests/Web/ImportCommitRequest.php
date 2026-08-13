<?php

namespace App\Http\Requests\Web;

use App\Support\ImportExport\Dataset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportCommitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'dataset' => ['required', Rule::enum(Dataset::class)],
            // Token pratinjau; keabsahannya (milik sesi ini, dataset-nya cocok,
            // berkasnya masih ada) diperiksa CommitImport.
            'token' => ['required', 'uuid'],
        ];
    }

    public function dataset(): Dataset
    {
        return Dataset::from($this->string('dataset')->toString());
    }

    /** @return array<string,string> */
    public function attributes(): array
    {
        return [
            'dataset' => 'jenis data',
            'token' => 'pratinjau',
        ];
    }
}
