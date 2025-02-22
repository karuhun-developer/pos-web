<?php

namespace App\Models\Pos;

use App\Enums\CommonStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProductVariant extends Model implements HasMedia
{
    use HasFactory, LogsActivity, InteractsWithMedia;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'price',
        'stock',
        'description',
        'status',
    ];

    protected $casts = [
        'price' => 'integer',
        'stock' => 'integer',
        'status' => CommonStatusEnum::class,
    ];

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logOnly(['*']);
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }
}
