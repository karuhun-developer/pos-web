<?php

namespace App\Models\Scopes;

use App\Support\StoreContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope tenant: setiap query model sync otomatis dibatasi ke toko aktif
 * (StoreContext). Tanpa toko aktif, tidak menambah batasan (mis. perintah CLI
 * lintas-toko) — endpoint API selalu men-set toko via SetCurrentStore.
 */
class StoreScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (StoreContext::has()) {
            $builder->where($model->getTable().'.store_id', StoreContext::id());
        }
    }
}
