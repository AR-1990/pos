<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use BelongsToStore;

    const REASONS = ['damage' => 'Damaged', 'expired' => 'Expired', 'count' => 'Stock count correction', 'lost' => 'Lost / theft', 'other' => 'Other'];

    protected $fillable = ['adjustment_no', 'store_id', 'user_id', 'date', 'reason', 'note'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }
}
