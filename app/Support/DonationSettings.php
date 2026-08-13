<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Cara berdonasi: QRIS, rekening bank, dan tautan Saweria.
 *
 * Sumbernya tabel `settings`, bukan .env — nomor rekening dan gambar QRIS
 * berubah tanpa alasan teknis apa pun, jadi superadmin harus bisa menggantinya
 * lewat halaman pengaturan tanpa menunggu deploy.
 */
class DonationSettings
{
    public const KEY = 'donation';

    /** Disk penyimpanan gambar QRIS. */
    public const DISK = 'public';

    /** @return array{qris_path: ?string, banks: list<array{bank:string,account_number:string,account_name:string}>, saweria_url: ?string, note: ?string} */
    public static function all(): array
    {
        /** @var array{qris_path: ?string, banks: list<array{bank:string,account_number:string,account_name:string}>, saweria_url: ?string, note: ?string} $settings */
        $settings = PlatformSettings::get(self::KEY, [
            'qris_path' => null,
            'banks' => [],
            'saweria_url' => null,
            'note' => null,
        ]);

        return $settings;
    }

    /** Bentuk yang dikirim ke halaman: path QRIS sudah jadi URL siap pakai. */
    public static function forDisplay(): array
    {
        $settings = self::all();

        return [
            'qris_url' => self::qrisUrl(),
            'banks' => array_values($settings['banks']),
            'saweria_url' => $settings['saweria_url'],
            'note' => $settings['note'],
        ];
    }

    public static function qrisUrl(): ?string
    {
        $path = self::all()['qris_path'];

        return $path ? Storage::disk(self::DISK)->url($path) : null;
    }

    /**
     * Kanal yang benar-benar bisa dipakai, urut seperti yang tampil di layar.
     *
     * Formulir donasi hanya menawarkan yang ada di sini: mencatat "saya sudah
     * transfer" lewat kanal yang belum dikonfigurasi tidak punya arti — tidak
     * ada ke mana orang bisa transfer.
     *
     * @return list<string>
     */
    public static function channels(): array
    {
        $settings = self::all();

        return array_values(array_filter([
            filled($settings['qris_path']) ? 'qris' : null,
            $settings['banks'] !== [] ? 'transfer' : null,
            filled($settings['saweria_url']) ? 'saweria' : null,
        ]));
    }

    /** Ada cara berdonasi yang benar-benar bisa dipakai? */
    public static function isOpen(): bool
    {
        return self::channels() !== [];
    }
}
