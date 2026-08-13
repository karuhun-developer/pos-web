<?php

namespace App\Http\Requests\Web;

use App\Support\ReportPeriod;
use App\Support\Spreadsheet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'format' => ['nullable', Rule::in(Spreadsheet::FORMATS)],
            'preset' => ['nullable', Rule::in(ReportPeriod::PRESETS)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ];
    }

    /**
     * Namanya BUKAN format(): Illuminate\Http\Request sudah punya
     * format($default = 'html') untuk negosiasi konten, dan menimpanya dengan
     * tanda tangan berbeda adalah fatal error PHP — yang baru meledak saat
     * kelas ini di-refleksi (mis. Ziggy memindai route), jadi seluruh halaman
     * ikut mati, bukan cuma ekspor.
     */
    public function fileFormat(): string
    {
        return $this->string('format', 'csv')->toString();
    }

    public function period(): ReportPeriod
    {
        return ReportPeriod::make(
            $this->query('preset'),
            $this->query('from'),
            $this->query('to'),
        );
    }

    /** @return array<string,string> */
    public function attributes(): array
    {
        return [
            'format' => 'format berkas',
            'preset' => 'rentang',
            'from' => 'tanggal awal',
            'to' => 'tanggal akhir',
        ];
    }
}
