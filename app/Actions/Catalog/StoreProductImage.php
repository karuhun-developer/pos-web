<?php

namespace App\Actions\Catalog;

use App\Actions\Sync\WriteEntity;
use App\Models\Media;
use Illuminate\Http\UploadedFile;

/**
 * Gambar produk tidak disimpan sebagai file lepas: ia jadi baris entity `media`
 * supaya ikut tersinkron ke Android. Byte-nya dikirim sebagai base64 lewat
 * WriteEntity → ApplyChange sudah punya jalur "base64 masuk, remote_url keluar"
 * (StoreMediaPayload), jadi tidak ada kode upload baru di sini.
 *
 * Nilai yang dikembalikan adalah ref `media://<id>` — format yang dibaca klien
 * (pos-kacaw/src/stores/media.ts) untuk kolom products.image_path.
 */
class StoreProductImage
{
    public function __construct(private readonly WriteEntity $writer) {}

    public function handle(UploadedFile $file): string
    {
        $binary = (string) file_get_contents($file->getRealPath());
        $hash = hash('sha256', $binary);

        // Dedup by hash, sama seperti klien: unggah gambar yang sama dua kali
        // tidak menggandakan baris media (dan tidak mengirim ulang byte-nya).
        $existing = Media::query()->where('hash', $hash)->whereNull('deleted_at')->first();
        if ($existing !== null) {
            return "media://{$existing->id}";
        }

        $size = @getimagesize($file->getRealPath());

        $id = $this->writer->upsert('media', [
            'mime' => $file->getMimeType() ?: 'image/jpeg',
            'width' => $size[0] ?? null,
            'height' => $size[1] ?? null,
            'bytes' => strlen($binary),
            'hash' => $hash,
            'data' => base64_encode($binary),
        ]);

        return "media://{$id}";
    }
}
