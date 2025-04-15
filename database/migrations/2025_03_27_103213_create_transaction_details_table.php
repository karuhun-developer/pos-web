<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Pos\Transaction::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\Pos\Product::class)->nullable()->constrained();
            $table->foreignIdFor(\App\Models\Pos\ProductVariant::class)->nullable()->constrained();
            $table->bigInteger('quantity');
            $table->bigInteger('price');
            $table->bigInteger('total');
            $table->bigInteger('discount_flat')->nullable();
            $table->bigInteger('discount_percent')->nullable();
            $table->bigInteger('discount_total')->nullable();
            $table->bigInteger('grand_total');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_details');
    }
};
