<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $fillable = ['transfer_no', 'from_store_id', 'to_store_id', 'user_id', 'date', 'note'];

    protected static function booted()
    {
        // Store staff see transfers leaving or arriving at their store
        static::addGlobalScope('store', function (Builder $query) {
            $storeId = Store::scopedId();
            if ($storeId !== null) {
                $query->where(fn ($q) => $q->where('from_store_id', $storeId)->orWhere('to_store_id', $storeId));
            }
        });
    }

    public function fromStore()
    {
        return $this->belongsTo(Store::class, 'from_store_id');
    }

    public function toStore()
    {
        return $this->belongsTo(Store::class, 'to_store_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }
}
