<?php

namespace App\Models\Pos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TransactionDetail extends Model implements HasMedia
{
    use HasFactory, LogsActivity, InteractsWithMedia;

    protected $fillable = [
        'transaction_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'price',
        'total',
        'discount_flat',
        'discount_percent',
        'discount_total',
        'grand_total',
    ];

    protected $casts = [
        'price' => 'integer',
        'total' => 'integer',
        'discount_flat' => 'integer',
        'discount_percent' => 'integer',
        'discount_total' => 'integer',
        'grand_total' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logOnly(['*']);
    }

    public function transaction() {
        return $this->belongsTo(Transaction::class);
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function productVariant() {
        return $this->belongsTo(ProductVariant::class);
    }
}
