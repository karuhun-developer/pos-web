<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Donasi adalah data PLATFORM, bukan entity sync: tidak punya store_id, tidak
 * pernah ikut ter-pull perangkat kasir, dan memakai timestamps biasa (bukan
 * epoch ms) karena tidak menempuh jalur ApplyChange.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            // Donatur boleh anonim / belum punya akun, jadi user_id nullable
            // dan donasinya tetap utuh kalau akunnya dihapus.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_id')->unique(); // juga dipakai sebagai route key
            $table->string('donor_name');
            $table->string('donor_email')->nullable();
            $table->unsignedBigInteger('amount'); // rupiah bulat, sama seperti uang lain di app ini
            $table->text('message')->nullable();
            $table->string('channel'); // qris | transfer | saweria
            // Nama & pesan donatur tampil di halaman publik, jadi setiap baris
            // menunggu ditinjau superadmin dulu — tanpa itu /dukung jadi papan
            // tulis terbuka untuk siapa pun yang mau menempel spam.
            $table->string('status'); // pending | approved | rejected
            $table->boolean('is_anonymous')->default(false);
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Panel superadmin memfilter dua kolom ini hampir di setiap query.
            $table->index(['status', 'created_at']);
            $table->index(['channel', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
