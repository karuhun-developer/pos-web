<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Delapan tabel entity yang disync dari FE POS Kacaw. PK = UUID (char36)
 * yang di-generate client; store_id di-isi server-side (tenant). Uang =
 * unsignedBigInteger (minor units), timestamp = unsignedBigInteger (epoch ms).
 * Lihat kontrak: docs/api-contract.md §5.
 */
return new class extends Migration
{
    /** Kolom sync universal (mirror SyncEntity di FE) + tenant. */
    private function syncColumns(Blueprint $table): void
    {
        $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
        $table->string('origin_device')->nullable();
        $table->unsignedBigInteger('created_at');
        $table->unsignedBigInteger('updated_at')->index(); // cursor pull + LWW
        $table->unsignedBigInteger('deleted_at')->nullable();
        $table->unsignedBigInteger('sync_version')->default(0);

        $table->index(['store_id', 'updated_at']);
    }

    private function entity(string $name, callable $columns): void
    {
        Schema::create($name, function (Blueprint $table) use ($columns) {
            $table->uuid('id')->primary();
            $columns($table);
            $this->syncColumns($table);
        });
    }

    public function up(): void
    {
        $this->entity('categories', function (Blueprint $t) {
            $t->string('name');
            $t->integer('sort_order')->default(0);
            $t->string('color')->nullable();
        });

        $this->entity('products', function (Blueprint $t) {
            $t->uuid('category_id')->nullable();
            $t->string('name');
            $t->string('sku')->nullable();
            $t->string('barcode')->nullable();
            $t->unsignedBigInteger('price')->default(0);
            $t->unsignedBigInteger('cost')->default(0);
            $t->unsignedTinyInteger('track_stock')->default(0);
            $t->integer('stock')->default(0);
            $t->string('image_path')->nullable();
            $t->unsignedTinyInteger('active')->default(1);
        });

        $this->entity('media', function (Blueprint $t) {
            $t->string('mime')->default('image/jpeg');
            $t->integer('width')->nullable();
            $t->integer('height')->nullable();
            $t->unsignedBigInteger('bytes')->nullable();
            $t->string('hash')->nullable()->index();
            $t->longText('data')->nullable(); // base64 (boleh di-drop saat remote_url terisi)
            $t->string('remote_url')->nullable();
        });

        $this->entity('cashier_sessions', function (Blueprint $t) {
            $t->unsignedBigInteger('opened_at');
            $t->unsignedBigInteger('closed_at')->nullable();
            $t->unsignedBigInteger('opening_cash')->default(0);
            $t->unsignedBigInteger('expected_cash')->default(0);
            $t->unsignedBigInteger('counted_cash')->nullable();
            $t->bigInteger('difference')->nullable(); // bisa negatif
            $t->string('status')->default('open'); // open|closed
            $t->string('opened_by')->nullable();
            $t->text('note')->nullable();
        });

        $this->entity('sales', function (Blueprint $t) {
            $t->uuid('session_id')->nullable();
            $t->string('number');
            $t->unsignedBigInteger('subtotal')->default(0);
            $t->unsignedBigInteger('discount')->default(0);
            $t->unsignedBigInteger('tax')->default(0);
            $t->unsignedBigInteger('total')->default(0);
            $t->unsignedBigInteger('paid')->default(0);
            $t->bigInteger('change_due')->default(0);
            $t->string('payment_method')->default('cash');
            $t->string('status')->default('completed'); // completed|void
            $t->unsignedBigInteger('sold_at');
        });

        $this->entity('sale_items', function (Blueprint $t) {
            $t->uuid('sale_id')->index();
            $t->uuid('product_id')->nullable();
            $t->string('name_snapshot');
            $t->unsignedBigInteger('price_snapshot')->default(0);
            $t->integer('qty')->default(1);
            $t->unsignedBigInteger('discount')->default(0);
            $t->unsignedBigInteger('line_total')->default(0);
        });

        $this->entity('cashflow_categories', function (Blueprint $t) {
            $t->string('name');
            $t->string('type'); // income|expense
            $t->unsignedTinyInteger('is_system')->default(0);
            $t->integer('sort_order')->default(0);
        });

        $this->entity('cashflow_entries', function (Blueprint $t) {
            $t->uuid('category_id')->nullable();
            $t->uuid('session_id')->nullable();
            $t->string('direction'); // debit|credit
            $t->unsignedBigInteger('amount')->default(0);
            $t->string('source')->default('manual'); // manual|sale
            $t->string('source_ref')->nullable();
            $t->text('note')->nullable();
            $t->unsignedBigInteger('occurred_at');
        });

        // Nomor struk unik per toko (bukan global) — gap skema didokumentasikan.
        Schema::table('sales', function (Blueprint $t) {
            $t->unique(['store_id', 'number']);
        });
    }

    public function down(): void
    {
        foreach ([
            'cashflow_entries', 'cashflow_categories', 'sale_items', 'sales',
            'cashier_sessions', 'media', 'products', 'categories',
        ] as $name) {
            Schema::dropIfExists($name);
        }
    }
};
