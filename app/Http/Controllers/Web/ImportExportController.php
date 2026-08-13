<?php

namespace App\Http\Controllers\Web;

use App\Actions\ImportExport\AnalyseImport;
use App\Actions\ImportExport\BuildTemplate;
use App\Actions\ImportExport\CommitImport;
use App\Actions\ImportExport\ExportDataset;
use App\Actions\ImportExport\PreviewImport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ExportRequest;
use App\Http\Requests\Web\ImportCommitRequest;
use App\Http\Requests\Web\ImportPreviewRequest;
use App\Models\User;
use App\Support\ImportExport\Dataset;
use App\Support\ReportPeriod;
use App\Support\Spreadsheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Impor/ekspor. Dua izin berbeda dipakai di sini dengan sengaja: mengunduh
 * cukup `reports.view`, sedangkan mengunggah mengikuti izin menulis data
 * aslinya (`Dataset::importPermission()`) — menulis ratusan baris sekaligus
 * jelas lebih berbahaya daripada membacanya.
 *
 * Impor berjalan dua langkah (pratinjau → terapkan). Hasil pratinjau dikirim
 * lewat flash session, bukan dengan me-render halaman dari POST, supaya pola
 * POST-redirect-GET tetap utuh dan refresh tidak mengunggah ulang berkasnya.
 */
class ImportExportController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_unless(
            $this->canExport($user) || $this->importableDatasets($user) !== [],
            403,
        );

        $period = ReportPeriod::make(
            $request->query('preset'),
            $request->query('from'),
            $request->query('to'),
        );

        return Inertia::render('ImportExport/Index', [
            'period' => $period->toArray(),
            'formats' => Spreadsheet::FORMATS,
            'can_export' => $this->canExport($user),
            'datasets' => $this->datasets($user),
            'preview' => $request->session()->get('import_preview'),
        ]);
    }

    public function export(ExportRequest $request, Dataset $dataset, ExportDataset $export): StreamedResponse
    {
        abort_unless($this->canExport($request->user()), 403);

        return $export->handle($dataset, $request->fileFormat(), $request->period());
    }

    public function template(ExportRequest $request, Dataset $dataset, BuildTemplate $template): StreamedResponse
    {
        abort_unless($this->canImport($request->user(), $dataset), 403);

        return $template->handle($dataset, $request->fileFormat());
    }

    public function preview(ImportPreviewRequest $request, PreviewImport $preview): RedirectResponse
    {
        $dataset = $request->dataset();
        abort_unless($this->canImport($request->user(), $dataset), 403);

        return back()->with('import_preview', $preview->handle($dataset, $request->upload()));
    }

    public function commit(ImportCommitRequest $request, CommitImport $commit): RedirectResponse
    {
        $dataset = $request->dataset();
        abort_unless($this->canImport($request->user(), $dataset), 403);

        $result = $commit->handle($dataset, $request->string('token')->toString());

        $message = "{$result['applied']} baris diterapkan.";

        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} baris dilewati karena error.";
        }

        return redirect()->route('io.index')->with('success', $message);
    }

    /**
     * Metadata setiap dataset untuk halaman: label, apakah terikat rentang
     * tanggal, dan — kalau bisa diimpor — kolom yang dikenali pembacanya.
     * Kolom diambil dari importer yang sama dengan yang memvalidasi berkas,
     * jadi keterangan di layar tidak pernah basi terhadap kodenya.
     *
     * @return list<array<string,mixed>>
     */
    private function datasets(User $user): array
    {
        $analyse = app(AnalyseImport::class);

        return array_map(function (Dataset $dataset) use ($user, $analyse) {
            $importable = $dataset->importer() !== null;

            return [
                'value' => $dataset->value,
                'label' => $dataset->label(),
                'description' => $dataset->description(),
                'uses_period' => $dataset->usesPeriod(),
                'importable' => $importable,
                'can_import' => $this->canImport($user, $dataset),
                'columns' => $importable ? $analyse->importer($dataset)->columns() : null,
            ];
        }, Dataset::cases());
    }

    /** @return list<Dataset> */
    private function importableDatasets(User $user): array
    {
        return array_values(array_filter(
            Dataset::cases(),
            fn (Dataset $dataset) => $this->canImport($user, $dataset),
        ));
    }

    private function canExport(User $user): bool
    {
        return $user->can('reports.view');
    }

    private function canImport(User $user, Dataset $dataset): bool
    {
        $permission = $dataset->importPermission();

        return $dataset->importer() !== null && $permission !== null && $user->can($permission);
    }
}
