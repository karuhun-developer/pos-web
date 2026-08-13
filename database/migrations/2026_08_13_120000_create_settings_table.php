<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan tingkat platform — bukan milik toko dan bukan entity sync.
 *
 * Key/value dengan payload JSON, bukan satu kolom per pengaturan: isinya
 * berubah karena keputusan produk (nomor rekening baru, kanal baru), bukan
 * karena skemanya berkembang, dan tiap perubahan kecil tidak layak menuntut
 * migration + deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
