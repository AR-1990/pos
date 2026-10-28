<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'user_id', 'opened_at', 'opening_cash', 'closed_at', 'expected_cash', 'closing_cash', 'difference', 'status', 'note'];

    protected $casts = ['opened_at' => 'datetime', 'closed_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    /**
     * The open shift of this user at this store, if any.
     */
    public static function currentFor($userId, $storeId): ?self
    {
        return static::withoutGlobalScope('store')
            ->where('user_id', $userId)
            ->where('store_id', $storeId)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

    /**
     * Cash that should be in the drawer: opening cash + cash taken - cash refunded.
     */
    public function expectedCash(): float
    {
        return (float) $this->opening_cash + (float) SalePayment::withoutGlobalScope('store')
            ->where('shift_id', $this->id)->where('method', 'cash')->sum('amount');
    }
}
