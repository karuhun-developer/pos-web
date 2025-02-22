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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(App\Models\Pos\ProductCategory::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(App\Models\Pos\ProductSubCategory::class)->nullable()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(App\Models\Pos\ProductMerk::class)->nullable()->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->integer('price');
            $table->integer('stock')->default(0);
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
