<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class SalePayment extends Model
{
    use BelongsToStore;

    const METHODS = ['cash' => 'Cash', 'card' => 'Card', 'bank_transfer' => 'Bank Transfer'];

    protected $fillable = ['sale_id', 'store_id', 'shift_id', 'user_id', 'customer_payment_id', 'method', 'amount', 'bank_id', 'reference'];

    protected $casts = ['amount' => 'float'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
