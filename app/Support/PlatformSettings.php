<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Pembaca/penulis pengaturan platform.
 *
 * Dimemoisasi per-request, bukan di-cache lintas request: pengaturan ini
 * dibaca paling banyak sekali per halaman, dan cache lintas request menambah
 * satu jalur invalidasi yang bisa membuat superadmin menyimpan nomor rekening
 * lalu tidak melihatnya berubah.
 */
class PlatformSettings
{
    /** @var array<string,mixed>|null */
    private static ?array $memo = null;

    /** @return array<string,mixed> */
    public static function get(string $key, array $default = []): array
    {
        self::$memo ??= Setting::query()->pluck('value', 'key')->all();

        $value = self::$memo[$key] ?? null;

        return is_array($value) ? [...$default, ...$value] : $default;
    }

    /** @param  array<string,mixed>  $value */
    public static function put(string $key, array $value): void
    {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        self::$memo = null;
    }

    /** Dipakai test yang mengganti pengaturan di tengah jalan. */
    public static function flush(): void
    {
        self::$memo = null;
    }
}
