<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class CustomerPayment extends Model
{
    use BelongsToStore;

    protected $fillable = ['customer_id', 'store_id', 'shift_id', 'user_id', 'date', 'amount', 'method', 'bank_id', 'note'];

    protected $casts = ['amount' => 'float'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function allocations()
    {
        return $this->hasMany(SalePayment::class);
    }
}
