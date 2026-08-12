<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simbologi barcode per produk (EAN13, CODE128, …) — dipakai klien buat
     * merender & memvalidasi barcode lewat JsBarcode.
     *
     * Default 'EAN13' (standar barcode ritel) supaya klien lama yang belum
     * mengirim kolom ini tetap dapat nilai yang masuk akal.
     *
     * Index (store_id, barcode) buat lookup barcode per toko — sengaja BUKAN
     * unique: push sync cuma menangkap SyncRejection, jadi pelanggaran unique
     * bakal melempar QueryException mentah dan menggagalkan seluruh batch.
     * Keunikan barcode ditegakkan di sisi klien (validasi form + skip saat impor).
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode_type', 20)->default('EAN13')->after('barcode');
            $table->index(['store_id', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['store_id', 'barcode']);
            $table->dropColumn('barcode_type');
        });
    }
};
