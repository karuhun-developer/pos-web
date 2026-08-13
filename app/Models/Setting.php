<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris pengaturan platform. Aksesnya lewat App\Support\PlatformSettings,
 * bukan model ini langsung — supaya ada satu tempat yang mengurus memoisasi.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'array'];
}
