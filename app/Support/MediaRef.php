<?php

namespace App\Support;

use App\Models\Media;

/**
 * products.image_path menyimpan ref "media://<uuid>", bukan URL — supaya
 * gambarnya bisa tinggal di SQLite lokal (base64) di sisi Android dan di
 * storage di sisi server. Web selalu memakai remote_url hasil upload.
 */
class MediaRef
{
    private const PREFIX = 'media://';

    public static function id(?string $ref): ?string
    {
        if ($ref === null || ! str_starts_with($ref, self::PREFIX)) {
            return null;
        }

        return substr($ref, strlen(self::PREFIX)) ?: null;
    }

    /**
     * Resolusi banyak ref sekaligus (satu query) → peta ref => URL.
     *
     * @param  iterable<string|null>  $refs
     * @return array<string,string>
     */
    public static function urls(iterable $refs): array
    {
        $ids = [];
        foreach ($refs as $ref) {
            $id = self::id($ref);
            if ($id !== null) {
                $ids[$id] = true;
            }
        }

        if ($ids === []) {
            return [];
        }

        return Media::query()
            ->whereIn('id', array_keys($ids))
            ->whereNotNull('remote_url')
            ->pluck('remote_url', 'id')
            ->mapWithKeys(fn (string $url, string $id) => [self::PREFIX.$id => $url])
            ->all();
    }
}
