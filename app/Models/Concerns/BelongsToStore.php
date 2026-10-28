<?php

namespace App\Models\Concerns;

use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;

/**
 * Limits a model's queries to the current user's store (see Store::scopedId()).
 * Admins see everything, or the store picked in the header switcher.
 * Use withoutGlobalScope('store') for deliberate cross-store queries.
 */
trait BelongsToStore
{
    public static function bootBelongsToStore(): void
    {
        // New rows default to the store the user works in / is looking at
        static::creating(function ($model) {
            $column = static::storeColumn();
            if (empty($model->{$column}) && ($storeId = Store::scopedId())) {
                $model->{$column} = $storeId;
            }
        });

        static::addGlobalScope('store', function (Builder $query) {
            $storeId = Store::scopedId();
            if ($storeId !== null) {
                $query->where($query->getModel()->getTable() . '.' . static::storeColumn(), $storeId);
            }
        });
    }

    public static function storeColumn(): string
    {
        return 'store_id';
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
