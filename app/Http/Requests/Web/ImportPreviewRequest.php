<?php

namespace App\Http\Requests\Web;

use App\Support\ImportExport\Dataset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class ImportPreviewRequest extends FormRequest
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
            // `txt` ikut diizinkan karena CSV yang ditebak PHP sebagai
            // text/plain akan gagal aturan `mimes:csv` — berkasnya sendiri
            // tetap dibaca sebagai CSV oleh openspout.
            'berkas' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
        ];
    }

    public function dataset(): Dataset
    {
        return Dataset::from($this->string('dataset')->toString());
    }

    public function upload(): UploadedFile
    {
        return $this->file('berkas');
    }

    /** @return array<string,string> */
    public function attributes(): array
    {
        return [
            'dataset' => 'jenis data',
            'berkas' => 'berkas',
        ];
    }

    /** @return array<string,string> */
    public function messages(): array
    {
        return [
            'berkas.mimes' => 'Berkas harus berformat CSV atau XLSX.',
            'berkas.max' => 'Ukuran berkas maksimal 5 MB.',
        ];
    }
}
