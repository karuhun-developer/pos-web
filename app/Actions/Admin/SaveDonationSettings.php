<?php

namespace App\Actions\Admin;

use App\Support\DonationSettings;
use App\Support\PlatformSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Menyimpan cara berdonasi: gambar QRIS, daftar rekening, dan tautan Saweria.
 */
class SaveDonationSettings
{
    /**
     * @param  array<string,mixed>  $attributes
     * @return array<string,mixed>
     */
    public function handle(array $attributes, ?UploadedFile $qris = null): array
    {
        $current = DonationSettings::all();

        $path = $current['qris_path'];

        if ($qris !== null) {
            $path = $qris->store('donation', DonationSettings::DISK);
            $this->forget($current['qris_path']);
        } elseif ($attributes['remove_qris'] ?? false) {
            $this->forget($path);
            $path = null;
        }

        $settings = [
            'qris_path' => $path,
            // Baris rekening yang kosong dibuang di sini, bukan divalidasi
            // sebagai error: formulirnya punya baris kosong bawaan supaya bisa
            // langsung diisi, dan menekan "simpan" tanpa menyentuhnya bukan
            // kesalahan pengguna.
            'banks' => $this->banks($attributes['banks'] ?? []),
            'saweria_url' => $attributes['saweria_url'] ?? null,
            'note' => $attributes['note'] ?? null,
        ];

        PlatformSettings::put(DonationSettings::KEY, $settings);

        return $settings;
    }

    /**
     * @param  array<int,array<string,string|null>>  $rows
     * @return list<array<string,string>>
     */
    private function banks(array $rows): array
    {
        return array_values(array_filter(array_map(fn (array $row) => [
            'bank' => trim((string) ($row['bank'] ?? '')),
            'account_number' => trim((string) ($row['account_number'] ?? '')),
            'account_name' => trim((string) ($row['account_name'] ?? '')),
        ], $rows), fn (array $row) => $row['bank'] !== '' && $row['account_number'] !== ''));
    }

    /** Gambar QRIS lama dihapus supaya disk tidak menumpuk berkas yatim. */
    private function forget(?string $path): void
    {
        if ($path) {
            Storage::disk(DonationSettings::DISK)->delete($path);
        }
    }
}
