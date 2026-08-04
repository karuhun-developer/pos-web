<?php

namespace App\Actions\Sync;

use Illuminate\Support\Facades\Storage;

/**
 * Menyimpan byte media (base64) ke disk penyimpanan dan mengembalikan URL publik.
 * Ini seam ke object storage: ganti disk di config/sync.php tanpa ubah pemanggil.
 */
class StoreMediaPayload
{
    /**
     * @return string remote_url hasil simpan
     */
    public function handle(string $id, string $base64, string $mime): string
    {
        $disk = config('sync.media_disk', 'public');
        $ext = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
        $path = "media/{$id}.{$ext}";

        Storage::disk($disk)->put($path, base64_decode($base64, true) ?: '');

        return Storage::disk($disk)->url($path);
    }
}
