<?php

namespace App\Actions\Platform;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Rilis terbaru POS Kacaw dibaca dari GitHub, bukan ditulis di kode: nomor versi
 * yang ditulis tangan pasti ketinggalan sekali dua kali rilis dan diam-diam
 * membohongi pengunjung. Hasilnya di-cache supaya landing tidak menembak GitHub
 * tiap kali dibuka, dan kalau GitHub tidak bisa dihubungi halamannya tetap
 * tampil — cuma tanpa nomor versi, dengan tombol unduh yang mengarah ke halaman
 * "latest release".
 */
class FetchAndroidRelease
{
    private const KEY = 'platform.android_release';

    private const ENDPOINT = 'https://api.github.com/repos/karuhun-developer/pos-android/releases/latest';

    /** @return array{version: ?string, url: string, apk: ?string, size: ?int, published_at: ?string} */
    public function handle(): array
    {
        $cached = Cache::get(self::KEY);

        if (is_array($cached)) {
            return $cached;
        }

        $release = $this->fetch();

        // Kegagalan ikut di-cache, tapi sebentar saja: kalau GitHub sedang mati
        // kita tidak menembaknya tiap request, tapi juga tidak menahan nomor
        // versi lama selama berjam-jam.
        Cache::put(self::KEY, $release, $release['version'] === null ? now()->addMinutes(5) : now()->addHours(6));

        return $release;
    }

    /** @return array{version: ?string, url: string, apk: ?string, size: ?int, published_at: ?string} */
    private function fetch(): array
    {
        try {
            $response = Http::acceptJson()->timeout(4)->get(self::ENDPOINT);
        } catch (ConnectionException) {
            return $this->fallback();
        }

        if ($response->failed() || blank($response->json('tag_name'))) {
            return $this->fallback();
        }

        $apk = collect($response->json('assets', []))
            ->first(fn (array $asset) => str_ends_with($asset['name'] ?? '', '.apk'));

        return [
            'version' => $response->json('tag_name'),
            'url' => $response->json('html_url') ?: config('platform.android_download'),
            'apk' => $apk['browser_download_url'] ?? null,
            'size' => $apk['size'] ?? null,
            'published_at' => $response->json('published_at'),
        ];
    }

    /** @return array{version: ?string, url: string, apk: ?string, size: ?int, published_at: ?string} */
    private function fallback(): array
    {
        return [
            'version' => null,
            'url' => config('platform.android_download'),
            'apk' => null,
            'size' => null,
            'published_at' => null,
        ];
    }
}
