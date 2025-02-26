<?php

namespace App\Models\Pos;

use App\Enums\CommonStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use HasFactory, LogsActivity, InteractsWithMedia;

    protected $fillable = [
        'product_category_id',
        'product_sub_category_id',
        'product_merk_id',
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

    public function category() {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function subCategory() {
        return $this->belongsTo(ProductSubCategory::class, 'product_sub_category_id');
    }

    public function merk() {
        return $this->belongsTo(ProductMerk::class, 'product_merk_id');
    }

    public function variants() {
        return $this->hasMany(ProductVariant::class);
    }
}
