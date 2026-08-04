<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('avatar_url')->nullable()->after('google_id');
            $table->foreignId('current_store_id')->nullable()->after('avatar_url')
                ->constrained('stores')->nullOnDelete();
            // password boleh null untuk user yang login via Google saja
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_store_id');
            $table->dropColumn(['google_id', 'avatar_url']);
        });
    }
};
