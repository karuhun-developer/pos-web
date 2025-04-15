<?php

namespace App\Models\Pos;

use App\Models\User;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Transaction extends Model implements HasMedia
{
    use HasFactory, LogsActivity, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'reference',
        'ref_number',
        'total',
        'discount_flat',
        'discount_percent',
        'discount_total',
        'grand_total',
        'status',
    ];

    protected $casts = [
        'total' => 'integer',
        'discount_flat' => 'integer',
        'discount_percent' => 'integer',
        'discount_total' => 'integer',
        'grand_total' => 'integer',
        'status' => PaymentStatusEnum::class,
    ];

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logOnly(['*']);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function details() {
        return $this->hasMany(TransactionDetail::class);
    }
}
