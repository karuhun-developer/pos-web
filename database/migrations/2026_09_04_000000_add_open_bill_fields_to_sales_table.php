<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sales', 'open_bill_label')) {
            Schema::table('sales', function (Blueprint $table): void {
                $table->string('open_bill_label')->nullable()->after('status');
            });
        }

        if (! Schema::hasColumn('sales', 'opened_at')) {
            Schema::table('sales', function (Blueprint $table): void {
                $table->unsignedBigInteger('opened_at')->nullable()->after('open_bill_label');
            });
        }

        if (! Schema::hasColumn('sales', 'origin_device_id')) {
            Schema::table('sales', function (Blueprint $table): void {
                $table->uuid('origin_device_id')->nullable()->after('opened_at');
            });
        }

        if (Schema::hasColumn('sales', 'sold_at') && ! $this->isSoldAtNullable()) {
            Schema::table('sales', function (Blueprint $table): void {
                $table->unsignedBigInteger('sold_at')->nullable()->change();
            });
        }

        if (! Schema::hasIndex('sales', ['status', 'opened_at'])) {
            Schema::table('sales', function (Blueprint $table): void {
                $table->index(['status', 'opened_at']);
            });
        }

        if (! Schema::hasIndex('sales', ['origin_device_id'])) {
            Schema::table('sales', function (Blueprint $table): void {
                $table->index('origin_device_id');
            });
        }
    }

    public function down(): void
    {
        DB::table('sales')
            ->whereNull('sold_at')
            ->update(['sold_at' => DB::raw('COALESCE(opened_at, updated_at, created_at, 0)')]);

        Schema::table('sales', function (Blueprint $table): void {
            $table->unsignedBigInteger('sold_at')->nullable(false)->change();
            $table->dropIndex('sales_status_opened_at_index');
            $table->dropIndex('sales_origin_device_id_index');
            $table->dropColumn(['origin_device_id', 'opened_at', 'open_bill_label']);
        });
    }

    private function isSoldAtNullable(): bool
    {
        foreach (Schema::getColumns('sales') as $column) {
            if ($column['name'] === 'sold_at') {
                return $column['nullable'];
            }
        }

        return false;
    }
};
